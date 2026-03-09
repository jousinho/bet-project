<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Betting;

use App\Application\Betting\Service\BetEvaluatorService;
use App\Domain\Betting\Criterion\BetCriterionInterface;
use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Entity\TeamExternalId;
use App\Domain\Betting\Repository\BetRepositoryInterface;
use App\Domain\Betting\Repository\TeamRepositoryInterface;
use App\Domain\Betting\Service\SeasonResolver;
use App\Tests\Integration\IntegrationTestCase;

class BetEvaluatorServiceTest extends IntegrationTestCase
{
    private TeamRepositoryInterface $teamRepository;
    private BetRepositoryInterface $betRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamRepository = static::getContainer()->get(TeamRepositoryInterface::class);
        $this->betRepository  = static::getContainer()->get(BetRepositoryInterface::class);
    }

    private function makeService(BetCriterionInterface ...$criteria): BetEvaluatorService
    {
        return new BetEvaluatorService($this->betRepository, new SeasonResolver(), $criteria);
    }

    private function createTeam(string $name, ?\DateTimeImmutable $fixtureDate = null): Team
    {
        $team = Team::create($name, 'PD');
        $team->setNextFixtureDate($fixtureDate ?? new \DateTimeImmutable('+1 day'));
        $team->setNextFixtureOpponentName('Rival FC');
        $this->teamRepository->save($team);
        return $team;
    }

    public function test_evaluateAll__when_criterion_is_met__should_create_bet(): void
    {
        $team = $this->createTeam('Real Madrid');

        $criterion = $this->createStub(BetCriterionInterface::class);
        $criterion->method('betType')->willReturn(Bet::TYPE_OVER_2_5);
        $criterion->method('isMet')->willReturn(true);

        $this->makeService($criterion)->evaluateAll([$team]);

        $bets = $this->betRepository->findAll();
        $this->assertCount(1, $bets);
        $this->assertSame(Bet::TYPE_OVER_2_5, $bets[0]->betType());
        $this->assertSame(Bet::STATUS_PENDING, $bets[0]->status());
    }

    public function test_evaluateAll__when_criterion_not_met__should_not_create_bet(): void
    {
        $team = $this->createTeam('Real Madrid');

        $criterion = $this->createStub(BetCriterionInterface::class);
        $criterion->method('betType')->willReturn(Bet::TYPE_OVER_2_5);
        $criterion->method('isMet')->willReturn(false);

        $this->makeService($criterion)->evaluateAll([$team]);

        $this->assertCount(0, $this->betRepository->findAll());
    }

    public function test_evaluateAll__when_called_twice__should_not_create_duplicate_bets(): void
    {
        $team = $this->createTeam('Real Madrid');

        $criterion = $this->createStub(BetCriterionInterface::class);
        $criterion->method('betType')->willReturn(Bet::TYPE_OVER_2_5);
        $criterion->method('isMet')->willReturn(true);

        $service = $this->makeService($criterion);
        $service->evaluateAll([$team]);
        $service->evaluateAll([$team]);

        $this->assertCount(1, $this->betRepository->findAll());
    }

    public function test_evaluateAll__team_without_fixture__should_not_create_bet(): void
    {
        $team = Team::create('FC Barcelona', 'PD');
        $this->teamRepository->save($team);

        $criterion = $this->createStub(BetCriterionInterface::class);
        $criterion->method('betType')->willReturn(Bet::TYPE_OVER_2_5);
        $criterion->method('isMet')->willReturn(true);

        $this->makeService($criterion)->evaluateAll([$team]);

        $this->assertCount(0, $this->betRepository->findAll());
    }
}
