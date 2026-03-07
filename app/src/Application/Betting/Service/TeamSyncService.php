<?php

declare(strict_types=1);

namespace App\Application\Betting\Service;

use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Repository\FootballDataProviderInterface;
use App\Domain\Betting\Repository\TeamRepositoryInterface;
use App\Domain\Betting\Service\FormCalculator;
use App\Domain\Betting\Service\GoalsCounterUpdater;

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

        if ($team->getNextFixtureDate() !== null && $team->getNextFixtureDate() > $now) {
            return;
        }

        $externalId = $this->getExternalId($team);
        if ($externalId === null) {
            return;
        }

        $matches = $this->footballDataProvider->getFinishedMatches($externalId, $team->getLeague(), 20);

        $homeMatches = array_values(array_filter($matches, fn($m) => $m['isHome']));
        $awayMatches = array_values(array_filter($matches, fn($m) => !$m['isHome']));

        $team->setFormLast8($this->formCalculator->calculate($matches, 8) ?: null);
        $team->setFormLast5Home($this->formCalculator->calculate($homeMatches, 5) ?: null);
        $team->setFormLast5Away($this->formCalculator->calculate($awayMatches, 5) ?: null);

        $this->goalsCounterUpdater->update($matches, $team);

        $fixture = $this->footballDataProvider->getNextFixture($externalId, $team->getLeague());
        if (!empty($fixture)) {
            $team->setNextFixtureDate(new \DateTimeImmutable($fixture['date']));
            $team->setNextFixtureOpponentId((int) $fixture['opponentExternalId']);
            $team->setNextFixtureIsHome($fixture['isHome']);
        } else {
            $team->setNextFixtureDate(null);
            $team->setNextFixtureOpponentId(null);
            $team->setNextFixtureIsHome(null);
        }

        $team->setLastSyncedAt($now);
        $this->teamRepository->save($team);
    }

    private function getExternalId(Team $team): ?string
    {
        foreach ($team->getExternalIds() as $externalId) {
            if ($externalId->getProvider() === self::PROVIDER) {
                return $externalId->getExternalId();
            }
        }

        return null;
    }
}
