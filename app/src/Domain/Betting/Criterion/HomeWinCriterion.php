<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\Entity\Team;

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

    public function isMet(Team $team): bool
    {
        if ($team->nextFixtureDate() === null || $team->nextFixtureIsHome() !== true) {
            return false;
        }

        $homeForm = $team->formLast5Home() ?? '';
        $opponentForm = $team->nextFixtureOpponentFormSituational() ?? '';

        $homeWins     = substr_count($homeForm, 'W');
        $opponentLosses = substr_count($opponentForm, 'L');

        return $homeWins >= 4 && $opponentLosses >= 3;
    }
}
