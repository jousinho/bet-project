<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\ValueObject\TeamSnapshot;

/**
 * Bet on over 2.5 goals when:
 *   - Team plays at home AND scored 3+ goals in at least 6 of their last 8 home matches
 *     (requires at least 5 home matches tracked, ratio >= 0.75)
 *   - OR team plays away AND scored 2+ goals in at least 5 of their last 8 away matches
 *     (requires at least 5 away matches tracked, ratio >= 0.625)
 */
class Over25Criterion implements BetCriterionInterface
{
    public function betType(): string
    {
        return Bet::TYPE_OVER_2_5;
    }

    public function isMet(TeamSnapshot $team): bool
    {
        if ($team->nextFixtureDate === null) {
            return false;
        }

        if ($team->nextFixtureIsHome === true) {
            return $team->matchesPlayedHome >= 5
                && $team->over25Home / $team->matchesPlayedHome >= 0.75;
        }

        return $team->matchesPlayedAway >= 5
            && $team->over15Away / $team->matchesPlayedAway >= 0.625;
    }
}
