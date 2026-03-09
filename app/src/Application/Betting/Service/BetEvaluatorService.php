<?php

declare(strict_types=1);

namespace App\Application\Betting\Service;

use App\Domain\Betting\Criterion\BetCriterionInterface;
use App\Domain\Betting\Entity\Bet;
use App\Domain\Tracking\Entity\Team;
use App\Domain\Betting\Repository\BetRepositoryInterface;
use App\Domain\Betting\Service\SeasonResolver;
use App\Domain\Betting\ValueObject\TeamSnapshot;

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

            $snapshot = $this->snapshotFromTeam($team);

            foreach ($this->criteria as $criterion) {
                if (!$criterion->isMet($snapshot)) {
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
                    $team->nextFixtureMatchday(),
                ));
            }
        }
    }

    private function snapshotFromTeam(Team $team): TeamSnapshot
    {
        return new TeamSnapshot(
            teamId: $team->id(),
            teamName: $team->name(),
            league: $team->league(),
            formLast8: $team->formLast8(),
            formLast5Home: $team->formLast5Home(),
            formLast5Away: $team->formLast5Away(),
            over25Home: $team->over25Home(),
            matchesPlayedHome: $team->matchesPlayedHome(),
            over15Away: $team->over15Away(),
            matchesPlayedAway: $team->matchesPlayedAway(),
            nextFixtureDate: $team->nextFixtureDate(),
            nextFixtureMatchday: $team->nextFixtureMatchday(),
            nextFixtureOpponentName: $team->nextFixtureOpponentName(),
            nextFixtureIsHome: $team->nextFixtureIsHome(),
            nextFixtureOpponentFormSituational: $team->nextFixtureOpponentFormSituational(),
            nextFixtureOpponentId: $team->nextFixtureOpponentId(),
        );
    }
}
