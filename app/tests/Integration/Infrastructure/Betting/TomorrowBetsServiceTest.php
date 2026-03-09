<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Betting;

use App\Application\Betting\Service\BetEvaluatorService;
use App\Application\Betting\Service\BetSettlementService;
use App\Application\Tracking\Service\TeamSyncService;
use App\Application\Betting\Service\TomorrowBetsService;
use App\Domain\Tracking\Entity\Team;
use App\Domain\Betting\Repository\BetRepositoryInterface;
use App\Domain\Tracking\Repository\FootballDataProviderInterface;
use App\Domain\Betting\Repository\TeamBetStatsRepositoryInterface;
use App\Domain\Tracking\Repository\TeamExternalIdRepositoryInterface;
use App\Domain\Tracking\Repository\TeamRepositoryInterface;
use App\Domain\Tracking\Service\FormCalculator;
use App\Domain\Tracking\Service\GoalsCounterUpdater;
use App\Domain\Betting\Service\SeasonResolver;
use App\Tests\Integration\IntegrationTestCase;

class TomorrowBetsServiceTest extends IntegrationTestCase
{
    private TeamRepositoryInterface $teamRepository;
    private TeamExternalIdRepositoryInterface $teamExternalIdRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamRepository = static::getContainer()->get(TeamRepositoryInterface::class);
        $this->teamExternalIdRepository = static::getContainer()->get(TeamExternalIdRepositoryInterface::class);
    }

    private function makeService(): TomorrowBetsService
    {
        $provider = $this->createStub(FootballDataProviderInterface::class);
        $provider->method('getFinishedMatches')->willReturn([]);
        $provider->method('getNextFixture')->willReturn([]);

        $syncService = new TeamSyncService(
            $this->teamRepository,
            $provider,
            new FormCalculator(),
            new GoalsCounterUpdater(),
        );

        $betRepository   = static::getContainer()->get(BetRepositoryInterface::class);
        $statsRepository = static::getContainer()->get(TeamBetStatsRepositoryInterface::class);
        $seasonResolver  = new SeasonResolver();

        $betEvaluatorService  = new BetEvaluatorService($betRepository, $seasonResolver, []);
        $betSettlementService = new BetSettlementService($betRepository, $statsRepository, $provider, $seasonResolver);

        return new TomorrowBetsService(
            $this->teamRepository,
            $this->teamExternalIdRepository,
            $syncService,
            $betEvaluatorService,
            $betSettlementService,
            [],
        );
    }

    private function createTeam(string $name, \DateTimeImmutable $fixtureDate, bool $isHome = true): Team
    {
        $team = Team::create($name, 'PD');
        $team->setNextFixtureDate($fixtureDate);
        $team->setNextFixtureIsHome($isHome);
        $this->teamRepository->save($team);

        return $team;
    }

    public function test_getting_bets_data__should_return_all_tracked_teams(): void
    {
        $this->createTeam('Real Madrid', new \DateTimeImmutable('+7 days'));
        $this->createTeam('FC Barcelona', new \DateTimeImmutable('+8 days'));

        $dtos = $this->makeService()->getData();

        $this->assertCount(2, $dtos);
    }

    public function test_getting_bets_data__should_order_teams_by_next_fixture_date(): void
    {
        $this->createTeam('FC Barcelona', new \DateTimeImmutable('+10 days'));
        $this->createTeam('Real Madrid', new \DateTimeImmutable('+3 days'));

        $dtos = $this->makeService()->getData();

        $this->assertSame('Real Madrid', $dtos[0]->teamName);
        $this->assertSame('FC Barcelona', $dtos[1]->teamName);
    }

    public function test_getting_bets_data__when_team_plays_tomorrow__should_be_highlighted(): void
    {
        $this->createTeam('Real Madrid', new \DateTimeImmutable('tomorrow noon'));

        $dtos = $this->makeService()->getData();

        $this->assertTrue($dtos[0]->highlightedTomorrow);
    }

    public function test_getting_bets_data__when_team_plays_at_home__should_use_home_stats(): void
    {
        $team = $this->createTeam('Real Madrid', new \DateTimeImmutable('+7 days'), true);
        $team->setOver25Home(7);
        $team->setMatchesPlayedHome(10);
        $team->setFormLast5Home('WWWDL');
        $this->teamRepository->save($team);

        $dtos = $this->makeService()->getData();

        $this->assertSame(7, $dtos[0]->teamOverCount);
        $this->assertSame(10, $dtos[0]->teamMatchesPlayed);
        $this->assertSame('WWWDL', $dtos[0]->formSituational);
    }

    public function test_getting_bets_data__when_team_plays_away__should_use_away_stats(): void
    {
        $team = $this->createTeam('Real Madrid', new \DateTimeImmutable('+7 days'), false);
        $team->setOver15Away(4);
        $team->setMatchesPlayedAway(8);
        $team->setFormLast5Away('WDLLW');
        $this->teamRepository->save($team);

        $dtos = $this->makeService()->getData();

        $this->assertSame(4, $dtos[0]->teamOverCount);
        $this->assertSame(8, $dtos[0]->teamMatchesPlayed);
        $this->assertSame('WDLLW', $dtos[0]->formSituational);
    }
}
