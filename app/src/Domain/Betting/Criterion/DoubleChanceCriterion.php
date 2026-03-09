<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\ValueObject\TeamSnapshot;

/**
 * Bet on double chance (home win or draw) when team plays at home and:
 *   - Won or drew at least 4 of their last 5 home matches (formLast5Home has 4+ W/D)
 *   - AND the opponent drew or lost at least 4 of their last 5 away matches (opponentFormSituational has 4+ D/L)
 */
class DoubleChanceCriterion implements BetCriterionInterface
{
    public function betType(): string
    {
        return Bet::TYPE_DOUBLE_CHANCE;
    }

    public function isMet(TeamSnapshot $team): bool
    {
        if ($team->nextFixtureDate === null || $team->nextFixtureIsHome !== true) {
            return false;
        }

        $homeNotLost    = substr_count($team->formLast5Home ?? '', 'W') + substr_count($team->formLast5Home ?? '', 'D');
        $opponentNotWon = substr_count($team->nextFixtureOpponentFormSituational ?? '', 'D') + substr_count($team->nextFixtureOpponentFormSituational ?? '', 'L');

        return $homeNotLost >= 4 && $opponentNotWon >= 4;
    }
}
