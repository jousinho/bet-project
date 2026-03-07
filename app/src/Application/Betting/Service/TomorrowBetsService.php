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
        $nextFixtureDate = $team->getNextFixtureDate();
        $isHighlighted = $nextFixtureDate !== null
            && $nextFixtureDate->format('Y-m-d') === $tomorrow->format('Y-m-d');

        $isHome = $team->getNextFixtureIsHome() ?? true;

        $opponentName = null;
        $opponentFormSituational = null;
        $opponentOverCount = 0;
        $opponentMatchesPlayed = 0;

        if ($isHighlighted && $team->getNextFixtureOpponentId() !== null) {
            $opponentExtId = $this->teamExternalIdRepository->findByProviderAndExternalId(
                'football-data.org',
                (string) $team->getNextFixtureOpponentId()
            );

            if ($opponentExtId !== null) {
                $opponent = $opponentExtId->getTeam();
                $this->teamSyncService->sync($opponent);

                $opponentName = $opponent->getName();
                $opponentFormSituational = $isHome
                    ? $opponent->getFormLast5Away()
                    : $opponent->getFormLast5Home();
                $opponentOverCount = $isHome
                    ? $opponent->getOver15Away()
                    : $opponent->getOver25Home();
                $opponentMatchesPlayed = $isHome
                    ? $opponent->getMatchesPlayedAway()
                    : $opponent->getMatchesPlayedHome();
            }
        }

        return new TeamBetDTO(
            teamName: $team->getName(),
            nextFixtureDate: $nextFixtureDate?->format('Y-m-d H:i') ?? '',
            nextFixtureOpponentName: $opponentName,
            isHome: $isHome,
            highlightedTomorrow: $isHighlighted,
            formLast8: $team->getFormLast8(),
            formSituational: $isHome ? $team->getFormLast5Home() : $team->getFormLast5Away(),
            opponentFormSituational: $opponentFormSituational,
            teamOverCount: $isHome ? $team->getOver25Home() : $team->getOver15Away(),
            teamMatchesPlayed: $isHome ? $team->getMatchesPlayedHome() : $team->getMatchesPlayedAway(),
            opponentOverCount: $opponentOverCount,
            opponentMatchesPlayed: $opponentMatchesPlayed,
        );
    }
}
