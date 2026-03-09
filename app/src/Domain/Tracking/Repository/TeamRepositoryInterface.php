<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Repository;

use App\Domain\Tracking\Entity\Team;

interface TeamRepositoryInterface
{
    public function findById(int $id): ?Team;

    public function findAll(): array;

    public function findAllOrderedByNextFixture(): array;

    public function save(Team $team): void;
}
