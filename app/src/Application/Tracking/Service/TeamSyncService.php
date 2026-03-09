<?php

declare(strict_types=1);

namespace App\Application\Tracking\Service;

use App\Domain\Tracking\Entity\Team;
use App\Domain\Tracking\Repository\FootballDataProviderInterface;
use App\Domain\Tracking\Repository\TeamRepositoryInterface;
use App\Domain\Tracking\Service\FormCalculator;
use App\Domain\Tracking\Service\GoalsCounterUpdater;

class TeamSyncService
{
    private const PROVIDER = 'football-data.org';

    public function __construct(
        private readonly TeamRepositoryInterface $teamRepository,
        private readonly FootballDataProviderInterface $footballDataProvider,
        private readonly FormCalculator $formCalculator,
        private readonly GoalsCounterUpdater $goalsCounterUpdater,
    ) {}

    public function sync(Team $team): void
    {
        $now = new \DateTimeImmutable();

        if ($this->isAlreadySynced($team, $now)) {
            return;
        }

        $externalId = $this->findProviderExternalId($team);
        if ($externalId === null) {
            return;
        }

        $this->updateMatchStats($team, $externalId);
        $this->updateNextFixture($team, $externalId);

        $team->setLastSyncedAt($now);
        $this->teamRepository->save($team);
    }

    private function isAlreadySynced(Team $team, \DateTimeImmutable $now): bool
    {
        if ($team->nextFixtureDate() === null || $team->nextFixtureDate() <= $now) {
            return false;
        }

        if ($team->nextFixtureOpponentFormSituational() === null) {
            return false;
        }

        $hoursUntilFixture = ($team->nextFixtureDate()->getTimestamp() - $now->getTimestamp()) / 3600;

        return $hoursUntilFixture >= 48;
    }

    private function findProviderExternalId(Team $team): ?string
    {
        foreach ($team->externalIds() as $externalId) {
            if ($externalId->provider() === self::PROVIDER) {
                return $externalId->externalId();
            }
        }

        return null;
    }

    private function updateMatchStats(Team $team, string $externalId): void
    {
        $matches = $this->footballDataProvider->getFinishedMatches($externalId, $team->league(), 20);

        $homeMatches = array_values(array_filter($matches, fn($m) => $m['isHome']));
        $awayMatches = array_values(array_filter($matches, fn($m) => !$m['isHome']));

        $team->setFormLast8($this->formCalculator->calculate($matches, 8) ?: null);
        $team->setFormLast5Home($this->formCalculator->calculate($homeMatches, 5) ?: null);
        $team->setFormLast5Away($this->formCalculator->calculate($awayMatches, 5) ?: null);

        $this->goalsCounterUpdater->update($matches, $team);
    }

    private function updateNextFixture(Team $team, string $externalId): void
    {
        $fixture = $this->footballDataProvider->getNextFixture($externalId, $team->league());

        if (!empty($fixture)) {
            $team->setNextFixtureDate(new \DateTimeImmutable($fixture['date']));
            $team->setNextFixtureOpponentId((int) $fixture['opponentExternalId']);
            $team->setNextFixtureOpponentName($fixture['opponentName']);
            $team->setNextFixtureIsHome($fixture['isHome']);
            $team->setNextFixtureMatchday($fixture['matchday'] ?? null);
            $team->setNextFixtureOpponentFormSituational(
                $this->fetchOpponentFormSituational(
                    $fixture['opponentExternalId'],
                    $team->league(),
                    $fixture['isHome'],
                )
            );
        } else {
            $team->setNextFixtureDate(null);
            $team->setNextFixtureOpponentId(null);
            $team->setNextFixtureOpponentName(null);
            $team->setNextFixtureOpponentFormSituational(null);
            $team->setNextFixtureIsHome(null);
            $team->setNextFixtureMatchday(null);
        }
    }

    private function fetchOpponentFormSituational(string $opponentExternalId, string $league, bool $teamIsHome): ?string
    {
        $matches = $this->footballDataProvider->getFinishedMatches($opponentExternalId, $league, 20);

        // When our team is home, the opponent plays away — we want their away form, and vice versa.
        $opponentIsHome = !$teamIsHome;
        $situationalMatches = array_values(array_filter($matches, fn($m) => $m['isHome'] === $opponentIsHome));

        return $this->formCalculator->calculate($situationalMatches, 5) ?: null;
    }
}
