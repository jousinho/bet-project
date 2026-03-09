<?php

declare(strict_types=1);

namespace App\Application\Betting\Service;

use App\Application\Betting\DTO\TeamBetDTO;
use App\Application\Betting\Service\BetEvaluatorService;
use App\Application\Betting\Service\BetSettlementService;
use App\Application\Tracking\Service\TeamSyncService;
use App\Domain\Betting\Criterion\BetCriterionInterface;
use App\Domain\Betting\ValueObject\TeamSnapshot;
use App\Domain\Tracking\Entity\Team;
use App\Domain\Tracking\Repository\TeamExternalIdRepositoryInterface;
use App\Domain\Tracking\Repository\TeamRepositoryInterface;

class TomorrowBetsService
{
    /** @param iterable<BetCriterionInterface> $criteria */
    public function __construct(
        private readonly TeamRepositoryInterface $teamRepository,
        private readonly TeamExternalIdRepositoryInterface $teamExternalIdRepository,
        private readonly TeamSyncService $teamSyncService,
        private readonly BetEvaluatorService $betEvaluatorService,
        private readonly BetSettlementService $betSettlementService,
        private readonly iterable $criteria,
    ) {}

    /** @return TeamBetDTO[] */
    public function getData(): array
    {
        $teams = $this->teamRepository->findAll();

        foreach ($teams as $team) {
            $this->teamSyncService->sync($team);
        }

        $this->betSettlementService->settleAll();
        $this->betEvaluatorService->evaluateAll($teams);

        $teams = $this->teamRepository->findAllOrderedByNextFixture();
        $tomorrow = new \DateTimeImmutable('tomorrow midnight');

        return array_map(fn(Team $team) => $this->buildDto($team, $tomorrow), $teams);
    }

    private function buildDto(Team $team, \DateTimeImmutable $tomorrow): TeamBetDTO
    {
        $nextFixtureDate = $team->nextFixtureDate();
        $isHighlighted = $nextFixtureDate !== null
            && $nextFixtureDate->format('Y-m-d') === $tomorrow->format('Y-m-d');

        $isHome = $team->nextFixtureIsHome() ?? true;

        $opponentName = $team->nextFixtureOpponentName();
        $opponentFormSituational = $team->nextFixtureOpponentFormSituational();
        $opponentOverCount = 0;
        $opponentMatchesPlayed = 0;

        if ($isHighlighted && $team->nextFixtureOpponentId() !== null) {
            $opponentExtId = $this->teamExternalIdRepository->findByProviderAndExternalId(
                'football-data.org',
                (string) $team->nextFixtureOpponentId()
            );

            if ($opponentExtId !== null) {
                $opponent = $opponentExtId->team();
                $this->teamSyncService->sync($opponent);

                $opponentOverCount = $isHome
                    ? $opponent->over15Away()
                    : $opponent->over25Home();
                $opponentMatchesPlayed = $isHome
                    ? $opponent->matchesPlayedAway()
                    : $opponent->matchesPlayedHome();
            }
        }

        $snapshot = $this->snapshotFromTeam($team);
        $activeBetTypes = [];
        foreach ($this->criteria as $criterion) {
            if ($criterion->isMet($snapshot)) {
                $activeBetTypes[] = $criterion->betType();
            }
        }

        return new TeamBetDTO(
            teamName: $team->name(),
            nextFixtureDate: $nextFixtureDate?->format('Y-m-d H:i') ?? '',
            nextFixtureOpponentName: $opponentName,
            isHome: $isHome,
            highlightedTomorrow: $isHighlighted,
            formLast8: $team->formLast8(),
            formSituational: $isHome ? $team->formLast5Home() : $team->formLast5Away(),
            opponentFormSituational: $opponentFormSituational,
            teamOverCount: $isHome ? $team->over25Home() : $team->over15Away(),
            teamMatchesPlayed: $isHome ? $team->matchesPlayedHome() : $team->matchesPlayedAway(),
            opponentOverCount: $opponentOverCount,
            opponentMatchesPlayed: $opponentMatchesPlayed,
            activeBetTypes: $activeBetTypes,
        );
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
