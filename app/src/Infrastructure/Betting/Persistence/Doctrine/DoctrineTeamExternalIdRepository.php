<?php

declare(strict_types=1);

namespace App\Infrastructure\Betting\Persistence\Doctrine;

use App\Domain\Betting\Entity\TeamExternalId;
use App\Domain\Betting\Repository\TeamExternalIdRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineTeamExternalIdRepository implements TeamExternalIdRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(TeamExternalId::class);
    }

    public function findByProviderAndExternalId(string $provider, string $externalId): ?TeamExternalId
    {
        return $this->repository->findOneBy([
            'provider' => $provider,
            'externalId' => $externalId,
        ]);
    }

    public function save(TeamExternalId $teamExternalId): void
    {
        $this->entityManager->persist($teamExternalId);
        $this->entityManager->flush();
    }
}
