<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\ValueObject\TeamSnapshot;

/**
 * Bet on over 3.5 goals when:
 *   - Team plays at home AND over35Home/matchesPlayedHome >= 0.50, min 5 home matches
 *   - OR team plays away AND over35Away/matchesPlayedAway >= 0.50, min 5 away matches
 */
class Over35Criterion implements BetCriterionInterface
{
    public function betType(): string
    {
        return Bet::TYPE_OVER_3_5;
    }

    public function isMet(TeamSnapshot $team): bool
    {
        if ($team->nextFixtureDate === null) {
            return false;
        }

        if ($team->nextFixtureIsHome === true) {
            return $team->matchesPlayedHome >= 5
                && $team->over35Home / $team->matchesPlayedHome >= 0.50;
        }

        return $team->matchesPlayedAway >= 5
            && $team->over35Away / $team->matchesPlayedAway >= 0.50;
    }
}
