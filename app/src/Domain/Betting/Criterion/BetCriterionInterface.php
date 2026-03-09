<?php

declare(strict_types=1);

namespace App\Domain\Betting\Criterion;

use App\Domain\Betting\ValueObject\TeamSnapshot;

interface BetCriterionInterface
{
    public function betType(): string;
    public function isMet(TeamSnapshot $team): bool;
}
