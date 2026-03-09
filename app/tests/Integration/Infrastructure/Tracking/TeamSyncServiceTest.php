<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Tracking;

use App\Application\Tracking\Service\TeamSyncService;
use App\Domain\Tracking\Entity\Team;
use App\Domain\Tracking\Entity\TeamExternalId;
use App\Domain\Tracking\Repository\FootballDataProviderInterface;
use App\Domain\Tracking\Repository\TeamExternalIdRepositoryInterface;
use App\Domain\Tracking\Repository\TeamRepositoryInterface;
use App\Domain\Tracking\Service\FormCalculator;
use App\Domain\Tracking\Service\GoalsCounterUpdater;
use App\Tests\Integration\IntegrationTestCase;

class TeamSyncServiceTest extends IntegrationTestCase
{
    private TeamRepositoryInterface $teamRepository;
    private TeamExternalIdRepositoryInterface $teamExternalIdRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamRepository = static::getContainer()->get(TeamRepositoryInterface::class);
        $this->teamExternalIdRepository = static::getContainer()->get(TeamExternalIdRepositoryInterface::class);
    }

    private function makeService(FootballDataProviderInterface $provider): TeamSyncService
    {
        return new TeamSyncService(
            $this->teamRepository,
            $provider,
            new FormCalculator(),
            new GoalsCounterUpdater(),
        );
    }

    private function createTeamWithExternalId(string $name, string $league, string $externalId): Team
    {
        $team = Team::create($name, $league);
        $ext = TeamExternalId::create($team, 'football-data.org', $externalId);
        $team->addExternalId($ext);
        $this->teamRepository->save($team);
        $this->teamExternalIdRepository->save($ext);

        return $team;
    }

    public function test_syncing_team__when_next_fixture_is_future__should_not_call_api(): void
    {
        $team = $this->createTeamWithExternalId('Real Madrid', 'PD', '86');
        $team->setNextFixtureDate(new \DateTimeImmutable('+7 days'));
        $team->setNextFixtureOpponentFormSituational('WDL');
        $this->teamRepository->save($team);

        $provider = $this->createMock(FootballDataProviderInterface::class);
        $provider->expects($this->never())->method('getFinishedMatches');
        $provider->expects($this->never())->method('getNextFixture');

        $this->makeService($provider)->sync($team);
    }

    public function test_syncing_team__when_next_fixture_is_within_48h__should_call_api(): void
    {
        $team = $this->createTeamWithExternalId('Real Madrid', 'PD', '86');
        $team->setNextFixtureDate(new \DateTimeImmutable('+24 hours'));
        $this->teamRepository->save($team);

        $provider = $this->createMock(FootballDataProviderInterface::class);
        $provider->expects($this->atLeastOnce())->method('getFinishedMatches')->willReturn([]);
        $provider->expects($this->once())->method('getNextFixture')->willReturn([]);

        $this->makeService($provider)->sync($team);
    }

    public function test_syncing_team__when_next_fixture_is_past__should_call_api_and_update_team(): void
    {
        $team = $this->createTeamWithExternalId('Real Madrid', 'PD', '86');
        $team->setNextFixtureDate(new \DateTimeImmutable('-1 day'));
        $this->teamRepository->save($team);
        $this->entityManager->clear();

        $team = $this->teamRepository->findById($team->id());

        $provider = $this->createStub(FootballDataProviderInterface::class);
        $provider->method('getFinishedMatches')->willReturnOnConsecutiveCalls(
            [
                ['isHome' => true,  'goalsScored' => 3, 'goalsAgainst' => 1, 'result' => 'W', 'date' => '2025-01-10T20:00:00Z'],
                ['isHome' => false, 'goalsScored' => 2, 'goalsAgainst' => 0, 'result' => 'W', 'date' => '2025-01-03T20:00:00Z'],
            ],
            // Segunda llamada: partidos del rival (FC Barcelona jugando fuera)
            [
                ['isHome' => false, 'goalsScored' => 1, 'goalsAgainst' => 0, 'result' => 'W', 'date' => '2025-01-08T20:00:00Z'],
                ['isHome' => false, 'goalsScored' => 0, 'goalsAgainst' => 1, 'result' => 'L', 'date' => '2025-01-01T20:00:00Z'],
            ]
        );
        $provider->method('getNextFixture')->willReturn([
            'date'               => '2025-03-15T20:00:00Z',
            'opponentExternalId' => '81',
            'opponentName'       => 'FC Barcelona',
            'isHome'             => true,
        ]);

        $this->makeService($provider)->sync($team);
        $this->entityManager->clear();

        $updated = $this->teamRepository->findById($team->id());

        $this->assertSame('WW', $updated->formLast8());
        $this->assertSame('W', $updated->formLast5Home());
        $this->assertSame('W', $updated->formLast5Away());
        $this->assertSame(1, $updated->over25Home());
        $this->assertSame(1, $updated->matchesPlayedHome());
        $this->assertSame(1, $updated->over15Away());
        $this->assertSame(1, $updated->matchesPlayedAway());
        $this->assertSame('2025-03-15', $updated->nextFixtureDate()->format('Y-m-d'));
        $this->assertSame(81, $updated->nextFixtureOpponentId());
        $this->assertSame('FC Barcelona', $updated->nextFixtureOpponentName());
        $this->assertSame('WL', $updated->nextFixtureOpponentFormSituational());
        $this->assertTrue($updated->nextFixtureIsHome());
    }

    public function test_syncing_team__when_synced__should_update_last_synced_at(): void
    {
        $team = $this->createTeamWithExternalId('FC Barcelona', 'PD', '81');
        $this->entityManager->clear();

        $team = $this->teamRepository->findById($team->id());

        $provider = $this->createStub(FootballDataProviderInterface::class);
        $provider->method('getFinishedMatches')->willReturn([]);
        $provider->method('getNextFixture')->willReturn([]);

        $before = new \DateTimeImmutable();
        $this->makeService($provider)->sync($team);
        $this->entityManager->clear();

        $updated = $this->teamRepository->findById($team->id());

        $this->assertNotNull($updated->lastSyncedAt());
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $updated->lastSyncedAt()->getTimestamp());
    }
}
