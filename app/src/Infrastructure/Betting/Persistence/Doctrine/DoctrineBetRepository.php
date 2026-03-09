<?php

declare(strict_types=1);

namespace App\Infrastructure\Betting\Persistence\Doctrine;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Tracking\Entity\Team;
use App\Domain\Betting\Repository\BetRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class DoctrineBetRepository implements BetRepositoryInterface
{
    private EntityRepository $repository;

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        $this->repository = $entityManager->getRepository(Bet::class);
    }

    public function save(Bet $bet): void
    {
        $this->entityManager->persist($bet);
        $this->entityManager->flush();
    }

    public function findPendingBefore(\DateTimeImmutable $date): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Bet::class, 'b')
            ->where('b.status = :status')
            ->andWhere('b.fixtureDate <= :date')
            ->setParameter('status', Bet::STATUS_PENDING)
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    public function existsForFixture(Team $team, \DateTimeImmutable $fixtureDate, string $betType): bool
    {
        return $this->entityManager->createQueryBuilder()
            ->select('COUNT(b.id)')
            ->from(Bet::class, 'b')
            ->where('b.team = :team')
            ->andWhere('b.fixtureDate = :fixtureDate')
            ->andWhere('b.betType = :betType')
            ->setParameter('team', $team)
            ->setParameter('fixtureDate', $fixtureDate)
            ->setParameter('betType', $betType)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }

    public function findAll(): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('b')
            ->from(Bet::class, 'b')
            ->orderBy('b.fixtureDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
