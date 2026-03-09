<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Service;

use App\Domain\Tracking\Entity\Team;

class GoalsCounterUpdater
{
    /**
     * Recalculates all goals counters on the team from scratch.
     *
     * @param array<int, array{isHome: bool, goalsScored: int, goalsAgainst: int, halfTimeGoalsScored: int, halfTimeGoalsAgainst: int}> $matches
     */
    public function update(array $matches, Team $team): void
    {
        $over25Home = $over15Home = $over35Home = $over05HtHome = $winBothHalvesHome = $matchesPlayedHome = 0;
        $over15Away = $over25Away = $over35Away = $over05HtAway = $winBothHalvesAway = $matchesPlayedAway = 0;

        foreach ($matches as $match) {
            $total  = $match['goalsScored'] + $match['goalsAgainst'];
            $htTotal = $match['halfTimeGoalsScored'] + $match['halfTimeGoalsAgainst'];
            $wonBothHalves = $match['halfTimeGoalsScored'] > $match['halfTimeGoalsAgainst']
                && $match['goalsScored'] > $match['goalsAgainst'];

            if ($match['isHome']) {
                $matchesPlayedHome++;
                if ($match['goalsScored'] >= 3) { $over25Home++; }
                if ($total >= 2) { $over15Home++; }
                if ($total >= 4) { $over35Home++; }
                if ($htTotal >= 1) { $over05HtHome++; }
                if ($wonBothHalves) { $winBothHalvesHome++; }
            } else {
                $matchesPlayedAway++;
                if ($match['goalsScored'] >= 2) { $over15Away++; }
                if ($total >= 3) { $over25Away++; }
                if ($total >= 4) { $over35Away++; }
                if ($htTotal >= 1) { $over05HtAway++; }
                if ($wonBothHalves) { $winBothHalvesAway++; }
            }
        }

        $team->setOver25Home($over25Home);
        $team->setOver15Home($over15Home);
        $team->setOver35Home($over35Home);
        $team->setOver05HtHome($over05HtHome);
        $team->setWinBothHalvesHome($winBothHalvesHome);
        $team->setMatchesPlayedHome($matchesPlayedHome);
        $team->setOver15Away($over15Away);
        $team->setOver25Away($over25Away);
        $team->setOver35Away($over35Away);
        $team->setOver05HtAway($over05HtAway);
        $team->setWinBothHalvesAway($winBothHalvesAway);
        $team->setMatchesPlayedAway($matchesPlayedAway);
    }
}
