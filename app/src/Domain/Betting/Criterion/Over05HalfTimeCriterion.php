<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\ValueObject\TeamSnapshot;

/**
 * Bet on over 0.5 half-time goals when:
 *   - Team plays at home AND over05HtHome/matchesPlayedHome >= 0.70, min 5 home matches
 *   - OR team plays away AND over05HtAway/matchesPlayedAway >= 0.70, min 5 away matches
 */
class Over05HalfTimeCriterion implements BetCriterionInterface
{
    public function betType(): string
    {
        return Bet::TYPE_OVER_05_HT;
    }

    public function isMet(TeamSnapshot $team): bool
    {
        if ($team->nextFixtureDate === null) {
            return false;
        }

        if ($team->nextFixtureIsHome === true) {
            return $team->matchesPlayedHome >= 5
                && $team->over05HtHome / $team->matchesPlayedHome >= 0.70;
        }

        return $team->matchesPlayedAway >= 5
            && $team->over05HtAway / $team->matchesPlayedAway >= 0.70;
    }
}
