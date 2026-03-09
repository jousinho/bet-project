<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Repository;

use App\Domain\Tracking\Entity\TeamExternalId;

interface TeamExternalIdRepositoryInterface
{
    public function findByProviderAndExternalId(string $provider, string $externalId): ?TeamExternalId;

    public function save(TeamExternalId $teamExternalId): void;
}
