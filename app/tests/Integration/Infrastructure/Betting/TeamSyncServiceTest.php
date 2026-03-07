<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Betting;

use App\Application\Betting\Service\TeamSyncService;
use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Entity\TeamExternalId;
use App\Domain\Betting\Repository\FootballDataProviderInterface;
use App\Domain\Betting\Repository\TeamExternalIdRepositoryInterface;
use App\Domain\Betting\Repository\TeamRepositoryInterface;
use App\Domain\Betting\Service\FormCalculator;
use App\Domain\Betting\Service\GoalsCounterUpdater;
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
        $team = new Team($name, $league);
        $ext = new TeamExternalId($team, 'football-data.org', $externalId);
        $team->addExternalId($ext);
        $this->teamRepository->save($team);
        $this->teamExternalIdRepository->save($ext);

        return $team;
    }

    public function test_syncing_team__when_next_fixture_is_future__should_not_call_api(): void
    {
        $team = $this->createTeamWithExternalId('Real Madrid', 'PD', '86');
        $team->setNextFixtureDate(new \DateTimeImmutable('+7 days'));
        $this->teamRepository->save($team);

        $provider = $this->createMock(FootballDataProviderInterface::class);
        $provider->expects($this->never())->method('getFinishedMatches');
        $provider->expects($this->never())->method('getNextFixture');

        $this->makeService($provider)->sync($team);
    }

    public function test_syncing_team__when_next_fixture_is_past__should_call_api_and_update_team(): void
    {
        $team = $this->createTeamWithExternalId('Real Madrid', 'PD', '86');
        $team->setNextFixtureDate(new \DateTimeImmutable('-1 day'));
        $this->teamRepository->save($team);
        $this->entityManager->clear();

        $team = $this->teamRepository->findById($team->getId());

        $provider = $this->createStub(FootballDataProviderInterface::class);
        $provider->method('getFinishedMatches')->willReturn([
            ['isHome' => true,  'goalsScored' => 3, 'goalsAgainst' => 1, 'result' => 'W', 'date' => '2025-01-10T20:00:00Z'],
            ['isHome' => false, 'goalsScored' => 2, 'goalsAgainst' => 0, 'result' => 'W', 'date' => '2025-01-03T20:00:00Z'],
        ]);
        $provider->method('getNextFixture')->willReturn([
            'date'               => '2025-03-15T20:00:00Z',
            'opponentExternalId' => '81',
            'isHome'             => true,
        ]);

        $this->makeService($provider)->sync($team);
        $this->entityManager->clear();

        $updated = $this->teamRepository->findById($team->getId());

        $this->assertSame('WW', $updated->getFormLast8());
        $this->assertSame('W', $updated->getFormLast5Home());
        $this->assertSame('W', $updated->getFormLast5Away());
        $this->assertSame(1, $updated->getOver25Home());
        $this->assertSame(1, $updated->getMatchesPlayedHome());
        $this->assertSame(1, $updated->getOver15Away());
        $this->assertSame(1, $updated->getMatchesPlayedAway());
        $this->assertSame('2025-03-15', $updated->getNextFixtureDate()->format('Y-m-d'));
        $this->assertSame(81, $updated->getNextFixtureOpponentId());
        $this->assertTrue($updated->getNextFixtureIsHome());
    }

    public function test_syncing_team__when_synced__should_update_last_synced_at(): void
    {
        $team = $this->createTeamWithExternalId('FC Barcelona', 'PD', '81');
        $this->entityManager->clear();

        $team = $this->teamRepository->findById($team->getId());

        $provider = $this->createStub(FootballDataProviderInterface::class);
        $provider->method('getFinishedMatches')->willReturn([]);
        $provider->method('getNextFixture')->willReturn([]);

        $before = new \DateTimeImmutable();
        $this->makeService($provider)->sync($team);
        $this->entityManager->clear();

        $updated = $this->teamRepository->findById($team->getId());

        $this->assertNotNull($updated->getLastSyncedAt());
        $this->assertGreaterThanOrEqual($before->getTimestamp(), $updated->getLastSyncedAt()->getTimestamp());
    }
}
