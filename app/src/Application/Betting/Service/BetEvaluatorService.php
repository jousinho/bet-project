<?php

declare(strict_types=1);

namespace App\Application\Betting\Service;

use App\Domain\Betting\Criterion\BetCriterionInterface;
use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Repository\BetRepositoryInterface;
use App\Domain\Betting\Service\SeasonResolver;

class BetEvaluatorService
{
    /** @param iterable<BetCriterionInterface> $criteria */
    public function __construct(
        private readonly BetRepositoryInterface $betRepository,
        private readonly SeasonResolver $seasonResolver,
        private readonly iterable $criteria,
    ) {}

    /** @param Team[] $teams */
    public function evaluateAll(array $teams): void
    {
        foreach ($teams as $team) {
            if ($team->nextFixtureDate() === null) {
                continue;
            }

            foreach ($this->criteria as $criterion) {
                if (!$criterion->isMet($team)) {
                    continue;
                }

                if ($this->betRepository->existsForFixture($team, $team->nextFixtureDate(), $criterion->betType())) {
                    continue;
                }

                $this->betRepository->save(Bet::create(
                    $team,
                    $team->nextFixtureDate(),
                    $team->nextFixtureOpponentName() ?? '',
                    $criterion->betType(),
                    $this->seasonResolver->resolve($team->nextFixtureDate()),
                ));
            }
        }
    }
}
