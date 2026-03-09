<?php

declare(strict_types=1);

namespace App\Infrastructure\Betting\Persistence\Doctrine;

use App\Domain\Tracking\Entity\Team;
use App\Domain\Betting\Entity\TeamBetStats;
use App\Domain\Betting\Repository\TeamBetStatsRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineTeamBetStatsRepository implements TeamBetStatsRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(TeamBetStats::class);
    }

    public function save(TeamBetStats $stats): void
    {
        $this->entityManager->persist($stats);
        $this->entityManager->flush();
    }

    public function findByTeamBetTypeSeason(Team $team, string $betType, string $season): ?TeamBetStats
    {
        return $this->repository->findOneBy([
            'team'    => $team,
            'betType' => $betType,
            'season'  => $season,
        ]);
    }

    public function findAll(): array
    {
        return $this->repository->findAll();
    }
}
