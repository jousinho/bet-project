<?php

declare(strict_types=1);

namespace App\Application\Betting\Service;

use App\Application\Betting\DTO\TeamBetDTO;
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

        return $this->buildDtos($teams, $tomorrow);
    }

    /**
     * @param Team[] $teams
     * @return TeamBetDTO[]
     */
    private function buildDtos(array $teams, \DateTimeImmutable $tomorrow): array
    {
        $trackedIdSet = [];
        foreach ($teams as $team) {
            $trackedIdSet[$team->id()] = $team;
        }

        $dtos = [];
        $seenFixtures = []; // "homeId-awayId-date" => index in $dtos

        foreach ($teams as $team) {
            if ($team->nextFixtureDate() === null) {
                $dtos[] = $this->buildNoFixtureDto($team, $tomorrow);
                continue;
            }

            $isHome = $team->nextFixtureIsHome() ?? true;
            $opponentId = $team->nextFixtureOpponentId();
            $opponentIsTracked = $opponentId !== null && isset($trackedIdSet[$opponentId]);

            // Build fixture key always from home perspective to detect cross-matches
            $homeId  = $isHome ? $team->id() : ($opponentId ?? 0);
            $awayId  = $isHome ? ($opponentId ?? 0) : $team->id();
            $dateKey = $team->nextFixtureDate()->format('Y-m-d');
            $key     = "{$homeId}-{$awayId}-{$dateKey}";

            if (isset($seenFixtures[$key])) {
                // Cross-match already built from home team perspective — merge bet types
                $existing = $dtos[$seenFixtures[$key]];
                $merged = array_values(array_unique(array_merge($existing->activeBetTypes, $this->getActiveBetTypes($team))));
                $dtos[$seenFixtures[$key]] = new TeamBetDTO(
                    homeTeamName: $existing->homeTeamName,
                    awayTeamName: $existing->awayTeamName,
                    nextFixtureDate: $existing->nextFixtureDate,
                    highlightedTomorrow: $existing->highlightedTomorrow,
                    trackedTeamNames: $existing->trackedTeamNames,
                    homeFormLast8: $existing->homeFormLast8,
                    homeFormSituational: $existing->homeFormSituational,
                    homeOver25: $existing->homeOver25,
                    homeOver15: $existing->homeOver15,
                    homeOver35: $existing->homeOver35,
                    homeMatchesPlayed: $existing->homeMatchesPlayed,
                    awayFormSituational: $existing->awayFormSituational,
                    awayOver15: $existing->awayOver15,
                    awayOver25: $existing->awayOver25,
                    awayOver35: $existing->awayOver35,
                    awayMatchesPlayed: $existing->awayMatchesPlayed,
                    activeBetTypes: $merged,
                );
                continue;
            }

            $opponent = $opponentIsTracked ? $trackedIdSet[$opponentId] : null;
            $dto = $this->buildMatchDto($team, $opponent, $isHome, $tomorrow);
            $seenFixtures[$key] = count($dtos);
            $dtos[] = $dto;
        }

        return $dtos;
    }

    private function buildMatchDto(Team $team, ?Team $opponent, bool $isHome, \DateTimeImmutable $tomorrow): TeamBetDTO
    {
        $fixtureDate     = $team->nextFixtureDate();
        $isHighlighted   = $fixtureDate !== null
            && $fixtureDate->format('Y-m-d') === $tomorrow->format('Y-m-d');

        $opponentName = $team->nextFixtureOpponentName() ?? 'TBD';

        if ($opponent === null) {
            $opponentExt = $team->nextFixtureOpponentId() !== null
                ? $this->teamExternalIdRepository->findByProviderAndExternalId(
                    'football-data.org',
                    (string) $team->nextFixtureOpponentId()
                )
                : null;
            if ($opponentExt !== null) {
                $opponent = $opponentExt->team();
                $this->teamSyncService->sync($opponent);
            }
        }

        $homeTeam = $isHome ? $team : $opponent;
        $awayTeam = $isHome ? $opponent : $team;

        $trackedNames = [$team->name()];

        return new TeamBetDTO(
            homeTeamName: $homeTeam?->name() ?? $opponentName,
            awayTeamName: $awayTeam?->name() ?? $opponentName,
            nextFixtureDate: $fixtureDate?->format('Y-m-d H:i') ?? '',
            highlightedTomorrow: $isHighlighted,
            trackedTeamNames: $trackedNames,
            homeFormLast8: $isHome ? $team->formLast8() : $opponent?->formLast8(),
            homeFormSituational: $isHome ? $team->formLast5Home() : $opponent?->formLast5Home(),
            homeOver25: $isHome ? $team->over25Home() : ($opponent?->over25Home() ?? 0),
            homeOver15: $isHome ? $team->over15Home() : ($opponent?->over15Home() ?? 0),
            homeOver35: $isHome ? $team->over35Home() : ($opponent?->over35Home() ?? 0),
            homeMatchesPlayed: $isHome ? $team->matchesPlayedHome() : ($opponent?->matchesPlayedHome() ?? 0),
            awayFormSituational: $isHome ? $opponent?->formLast5Away() : $team->formLast5Away(),
            awayOver15: $isHome ? ($opponent?->over15Away() ?? 0) : $team->over15Away(),
            awayOver25: $isHome ? ($opponent?->over25Away() ?? 0) : $team->over25Away(),
            awayOver35: $isHome ? ($opponent?->over35Away() ?? 0) : $team->over35Away(),
            awayMatchesPlayed: $isHome ? ($opponent?->matchesPlayedAway() ?? 0) : $team->matchesPlayedAway(),
            activeBetTypes: $this->getActiveBetTypes($team),
        );
    }

    private function buildNoFixtureDto(Team $team, \DateTimeImmutable $tomorrow): TeamBetDTO
    {
        return new TeamBetDTO(
            homeTeamName: $team->name(),
            awayTeamName: '',
            nextFixtureDate: '',
            highlightedTomorrow: false,
            trackedTeamNames: [$team->name()],
            homeFormLast8: $team->formLast8(),
            homeFormSituational: $team->formLast5Home(),
            homeOver25: $team->over25Home(),
            homeOver15: $team->over15Home(),
            homeOver35: $team->over35Home(),
            homeMatchesPlayed: $team->matchesPlayedHome(),
            awayFormSituational: $team->formLast5Away(),
            awayOver15: $team->over15Away(),
            awayOver25: $team->over25Away(),
            awayOver35: $team->over35Away(),
            awayMatchesPlayed: $team->matchesPlayedAway(),
            activeBetTypes: [],
        );
    }

    /** @return string[] */
    private function getActiveBetTypes(Team $team): array
    {
        $snapshot = $this->snapshotFromTeam($team);
        $types = [];
        foreach ($this->criteria as $criterion) {
            if ($criterion->isMet($snapshot)) {
                $types[] = $criterion->betType();
            }
        }
        return $types;
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
            over15Home: $team->over15Home(),
            over35Home: $team->over35Home(),
            over05HtHome: $team->over05HtHome(),
            winBothHalvesHome: $team->winBothHalvesHome(),
            matchesPlayedHome: $team->matchesPlayedHome(),
            over15Away: $team->over15Away(),
            over25Away: $team->over25Away(),
            over35Away: $team->over35Away(),
            over05HtAway: $team->over05HtAway(),
            winBothHalvesAway: $team->winBothHalvesAway(),
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
