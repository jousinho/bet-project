<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\ValueObject\TeamSnapshot;

/**
 * Bet on home win when team plays at home and:
 *   - Won at least 4 of their last 5 home matches (formLast5Home has 4+ W's)
 *   - AND the opponent lost at least 3 of their last 5 away matches (opponentFormSituational has 3+ L's)
 */
class HomeWinCriterion implements BetCriterionInterface
{
    public function betType(): string
    {
        return Bet::TYPE_HOME_WIN;
    }

    public function isMet(TeamSnapshot $team): bool
    {
        if ($team->nextFixtureDate === null || $team->nextFixtureIsHome !== true) {
            return false;
        }

        $homeWins       = substr_count($team->formLast5Home ?? '', 'W');
        $opponentLosses = substr_count($team->nextFixtureOpponentFormSituational ?? '', 'L');

        return $homeWins >= 4 && $opponentLosses >= 3;
    }
}
