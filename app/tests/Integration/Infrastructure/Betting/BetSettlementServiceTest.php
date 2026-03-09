<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Betting;

use App\Application\Betting\Service\BetSettlementService;
use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Entity\TeamExternalId;
use App\Domain\Betting\Repository\BetRepositoryInterface;
use App\Domain\Betting\Repository\FootballDataProviderInterface;
use App\Domain\Betting\Repository\TeamBetStatsRepositoryInterface;
use App\Domain\Betting\Repository\TeamExternalIdRepositoryInterface;
use App\Domain\Betting\Repository\TeamRepositoryInterface;
use App\Domain\Betting\Service\SeasonResolver;
use App\Tests\Integration\IntegrationTestCase;

class BetSettlementServiceTest extends IntegrationTestCase
{
    private TeamRepositoryInterface $teamRepository;
    private TeamExternalIdRepositoryInterface $teamExternalIdRepository;
    private BetRepositoryInterface $betRepository;
    private TeamBetStatsRepositoryInterface $statsRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamRepository           = static::getContainer()->get(TeamRepositoryInterface::class);
        $this->teamExternalIdRepository = static::getContainer()->get(TeamExternalIdRepositoryInterface::class);
        $this->betRepository            = static::getContainer()->get(BetRepositoryInterface::class);
        $this->statsRepository          = static::getContainer()->get(TeamBetStatsRepositoryInterface::class);
    }

    private function makeService(FootballDataProviderInterface $provider): BetSettlementService
    {
        return new BetSettlementService(
            $this->betRepository,
            $this->statsRepository,
            $provider,
            new SeasonResolver(),
        );
    }

    private function createTeamWithBet(string $betType, \DateTimeImmutable $fixtureDate): array
    {
        $team = Team::create('Real Madrid', 'PD');
        $ext  = TeamExternalId::create($team, 'football-data.org', '86');
        $team->addExternalId($ext);
        $this->teamRepository->save($team);
        $this->teamExternalIdRepository->save($ext);

        $bet = Bet::create($team, $fixtureDate, 'Rival FC', $betType, '2025/26');
        $this->betRepository->save($bet);

        return [$team, $bet];
    }

    public function test_settleAll__over25_with_3_goals__should_mark_won(): void
    {
        $fixtureDate = new \DateTimeImmutable('-1 day');
        [, $bet] = $this->createTeamWithBet(Bet::TYPE_OVER_2_5, $fixtureDate);

        $provider = $this->createStub(FootballDataProviderInterface::class);
        $provider->method('getFinishedMatches')->willReturn([
            ['date' => $fixtureDate->format('c'), 'isHome' => true, 'goalsScored' => 2, 'goalsAgainst' => 1, 'result' => 'W'],
        ]);

        $this->makeService($provider)->settleAll();
        $this->entityManager->clear();

        $settled = $this->betRepository->findAll()[0];
        $this->assertSame(Bet::STATUS_WON, $settled->status());
        $this->assertNotNull($settled->settledAt());
    }

    public function test_settleAll__over25_with_2_goals__should_mark_lost(): void
    {
        $fixtureDate = new \DateTimeImmutable('-1 day');
        [, $bet] = $this->createTeamWithBet(Bet::TYPE_OVER_2_5, $fixtureDate);

        $provider = $this->createStub(FootballDataProviderInterface::class);
        $provider->method('getFinishedMatches')->willReturn([
            ['date' => $fixtureDate->format('c'), 'isHome' => true, 'goalsScored' => 1, 'goalsAgainst' => 1, 'result' => 'D'],
        ]);

        $this->makeService($provider)->settleAll();
        $this->entityManager->clear();

        $this->assertSame(Bet::STATUS_LOST, $this->betRepository->findAll()[0]->status());
    }

    public function test_settleAll__should_create_team_bet_stats(): void
    {
        $fixtureDate = new \DateTimeImmutable('-1 day');
        [$team] = $this->createTeamWithBet(Bet::TYPE_OVER_2_5, $fixtureDate);

        $provider = $this->createStub(FootballDataProviderInterface::class);
        $provider->method('getFinishedMatches')->willReturn([
            ['date' => $fixtureDate->format('c'), 'isHome' => true, 'goalsScored' => 2, 'goalsAgainst' => 1, 'result' => 'W'],
        ]);

        $this->makeService($provider)->settleAll();
        $this->entityManager->clear();

        $stats = $this->statsRepository->findByTeamBetTypeSeason($team, Bet::TYPE_OVER_2_5, '2025/26');
        $this->assertNotNull($stats);
        $this->assertSame(1, $stats->timesBet());
        $this->assertSame(1, $stats->timesWon());
        $this->assertSame(100.0, $stats->winRate());
    }

    public function test_settleAll__when_no_match_found__should_leave_bet_pending(): void
    {
        $fixtureDate = new \DateTimeImmutable('-1 day');
        $this->createTeamWithBet(Bet::TYPE_OVER_2_5, $fixtureDate);

        $provider = $this->createStub(FootballDataProviderInterface::class);
        $provider->method('getFinishedMatches')->willReturn([]);

        $this->makeService($provider)->settleAll();

        $this->assertSame(Bet::STATUS_PENDING, $this->betRepository->findAll()[0]->status());
    }
}
