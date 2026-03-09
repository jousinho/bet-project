<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\Entity\Team;

interface BetCriterionInterface
{
    public function betType(): string;
    public function isMet(Team $team): bool;
}
