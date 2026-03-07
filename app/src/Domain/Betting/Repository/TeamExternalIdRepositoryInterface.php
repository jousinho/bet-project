<?php

declare(strict_types=1);

namespace App\Domain\Betting\Repository;

use App\Domain\Betting\Entity\TeamExternalId;

interface TeamExternalIdRepositoryInterface
{
    public function findByProviderAndExternalId(string $provider, string $externalId): ?TeamExternalId;

    public function save(TeamExternalId $teamExternalId): void;
}
