<?php

declare(strict_types=1);

namespace App\Domain\Betting\Repository;

use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Entity\TeamBetStats;

interface TeamBetStatsRepositoryInterface
{
    public function save(TeamBetStats $stats): void;
    public function findByTeamBetTypeSeason(Team $team, string $betType, string $season): ?TeamBetStats;
    public function findAll(): array;
}
