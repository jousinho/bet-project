<?php

declare(strict_types=1);

namespace App\Application\Betting\Service;

use App\Application\Betting\DTO\TeamBetDTO;
use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Repository\TeamExternalIdRepositoryInterface;
use App\Domain\Betting\Repository\TeamRepositoryInterface;

class TomorrowBetsService
{
    public function __construct(
        private readonly TeamRepositoryInterface $teamRepository,
        private readonly TeamExternalIdRepositoryInterface $teamExternalIdRepository,
        private readonly TeamSyncService $teamSyncService,
    ) {}

    /** @return TeamBetDTO[] */
    public function getData(): array
    {
        $teams = $this->teamRepository->findAll();

        foreach ($teams as $team) {
            $this->teamSyncService->sync($team);
        }

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
        );
    }
}
