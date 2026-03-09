<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\ValueObject\TeamSnapshot;

/**
 * Bet on away win when team plays away and:
 *   - Won at least 3 of their last 5 away matches (formLast5Away has 3+ W's)
 *   - AND the opponent lost at least 3 of their last 5 home matches (opponentFormSituational has 3+ L's)
 */
class AwayWinCriterion implements BetCriterionInterface
{
    public function betType(): string
    {
        return Bet::TYPE_AWAY_WIN;
    }

    public function isMet(TeamSnapshot $team): bool
    {
        if ($team->nextFixtureDate === null || $team->nextFixtureIsHome !== false) {
            return false;
        }

        $awayWins       = substr_count($team->formLast5Away ?? '', 'W');
        $opponentLosses = substr_count($team->nextFixtureOpponentFormSituational ?? '', 'L');

        return $awayWins >= 3 && $opponentLosses >= 3;
    }
}
