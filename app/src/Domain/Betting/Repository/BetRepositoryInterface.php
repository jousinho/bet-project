<?php

declare(strict_types=1);

namespace App\Domain\Betting\Repository;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\Entity\Team;

interface BetRepositoryInterface
{
    public function save(Bet $bet): void;
    public function findPendingBefore(\DateTimeImmutable $date): array;
    public function existsForFixture(Team $team, \DateTimeImmutable $fixtureDate, string $betType): bool;
    public function findAll(): array;
}
