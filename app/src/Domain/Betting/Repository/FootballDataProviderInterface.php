<?php

declare(strict_types=1);

namespace App\Domain\Betting\Repository;

interface FootballDataProviderInterface
{
    /**
     * Returns the next scheduled league fixture for the team.
     *
     * @return array{date: string, opponentExternalId: string, opponentName: string, isHome: bool}|array{}
     */
    public function getNextFixture(string $externalTeamId, string $competition): array;

    /**
     * Returns the last N finished league matches for the team.
     *
     * Each match: ['date' => string, 'isHome' => bool, 'goalsScored' => int, 'goalsAgainst' => int, 'result' => string]
     *
     * @return array<int, array{date: string, isHome: bool, goalsScored: int, goalsAgainst: int, result: string}>
     */
    public function getFinishedMatches(string $externalTeamId, string $competition, int $limit): array;
}
