<?php

declare(strict_types=1);

namespace App\Domain\Betting\Service;

use App\Domain\Betting\Entity\Team;

class GoalsCounterUpdater
{
    /**
     * Recalculates goals counters on the team from scratch.
     * Home: over +2.5 (3+ goals scored). Away: over +1.5 (2+ goals scored).
     *
     * @param array<int, array{isHome: bool, goalsScored: int}> $matches
     */
    public function update(array $matches, Team $team): void
    {
        $over25Home = 0;
        $matchesPlayedHome = 0;
        $over15Away = 0;
        $matchesPlayedAway = 0;

        foreach ($matches as $match) {
            if ($match['isHome']) {
                $matchesPlayedHome++;
                if ($match['goalsScored'] >= 3) {
                    $over25Home++;
                }
            } else {
                $matchesPlayedAway++;
                if ($match['goalsScored'] >= 2) {
                    $over15Away++;
                }
            }
        }

        $team->setOver25Home($over25Home);
        $team->setMatchesPlayedHome($matchesPlayedHome);
        $team->setOver15Away($over15Away);
        $team->setMatchesPlayedAway($matchesPlayedAway);
    }
}
