<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\ValueObject\TeamSnapshot;

/**
 * Bet on under 2.5 goals when:
 *   - Team plays at home AND (matchesPlayedHome - over25Home)/matchesPlayedHome >= 0.60, min 5 home matches
 *   - OR team plays away AND (matchesPlayedAway - over25Away)/matchesPlayedAway >= 0.60, min 5 away matches
 */
class Under25Criterion implements BetCriterionInterface
{
    public function betType(): string
    {
        return Bet::TYPE_UNDER_2_5;
    }

    public function isMet(TeamSnapshot $team): bool
    {
        if ($team->nextFixtureDate === null) {
            return false;
        }

        if ($team->nextFixtureIsHome === true) {
            return $team->matchesPlayedHome >= 5
                && ($team->matchesPlayedHome - $team->over25Home) / $team->matchesPlayedHome >= 0.60;
        }

        return $team->matchesPlayedAway >= 5
            && ($team->matchesPlayedAway - $team->over25Away) / $team->matchesPlayedAway >= 0.60;
    }
}
