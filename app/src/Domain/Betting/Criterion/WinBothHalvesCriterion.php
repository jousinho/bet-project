<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\ValueObject\TeamSnapshot;

/**
 * Bet on win both halves when:
 *   - Team plays at home AND winBothHalvesHome/matchesPlayedHome >= 0.40, min 5 home matches
 *   - OR team plays away AND winBothHalvesAway/matchesPlayedAway >= 0.40, min 5 away matches
 */
class WinBothHalvesCriterion implements BetCriterionInterface
{
    public function betType(): string
    {
        return Bet::TYPE_WIN_BOTH_HALVES;
    }

    public function isMet(TeamSnapshot $team): bool
    {
        if ($team->nextFixtureDate === null) {
            return false;
        }

        if ($team->nextFixtureIsHome === true) {
            return $team->matchesPlayedHome >= 5
                && $team->winBothHalvesHome / $team->matchesPlayedHome >= 0.40;
        }

        return $team->matchesPlayedAway >= 5
            && $team->winBothHalvesAway / $team->matchesPlayedAway >= 0.40;
    }
}
