<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Repository;

interface FootballDataProviderInterface
{
    /**
     * Returns the next scheduled league fixture for the team.
     *
     * @return array{date: string, opponentExternalId: string, opponentName: string, isHome: bool, matchday: int}|array{}
     */
    public function getNextFixture(string $externalTeamId, string $competition): array;

    /**
     * Returns the last N finished league matches for the team.
     *
     * Each match: ['date', 'isHome', 'goalsScored', 'goalsAgainst', 'result', 'halfTimeGoalsScored', 'halfTimeGoalsAgainst']
     *
     * @return array<int, array{date: string, isHome: bool, goalsScored: int, goalsAgainst: int, result: string, halfTimeGoalsScored: int, halfTimeGoalsAgainst: int}>
     */
    public function getFinishedMatches(string $externalTeamId, string $competition, int $limit): array;
}
