<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Betting;

use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Entity\TeamExternalId;
use App\Domain\Betting\Repository\TeamExternalIdRepositoryInterface;
use App\Domain\Betting\Repository\TeamRepositoryInterface;
use App\Tests\Integration\IntegrationTestCase;

class DoctrineTeamRepositoryTest extends IntegrationTestCase
{
    private TeamRepositoryInterface $teamRepository;
    private TeamExternalIdRepositoryInterface $teamExternalIdRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamRepository = static::getContainer()->get(TeamRepositoryInterface::class);
        $this->teamExternalIdRepository = static::getContainer()->get(TeamExternalIdRepositoryInterface::class);
    }

    public function test_persisting_team__should_be_retrievable_from_database(): void
    {
        $team = new Team('Real Madrid', 'PD');
        $this->teamRepository->save($team);
        $this->entityManager->clear();

        $found = $this->teamRepository->findById($team->getId());

        $this->assertNotNull($found);
        $this->assertSame('Real Madrid', $found->getName());
        $this->assertSame('PD', $found->getLeague());
    }

    public function test_persisting_team_external_id__should_be_linked_to_team(): void
    {
        $team = new Team('FC Barcelona', 'PD');
        $externalId = new TeamExternalId($team, 'football-data.org', '81');
        $team->addExternalId($externalId);

        $this->teamRepository->save($team);
        $this->teamExternalIdRepository->save($externalId);
        $this->entityManager->clear();

        $found = $this->teamExternalIdRepository->findByProviderAndExternalId('football-data.org', '81');

        $this->assertNotNull($found);
        $this->assertSame('football-data.org', $found->getProvider());
        $this->assertSame('FC Barcelona', $found->getTeam()->getName());
    }

    public function test_finding_team_by_external_id_and_provider__should_return_correct_team(): void
    {
        $team = new Team('Bayern Munich', 'BL1');
        $externalId = new TeamExternalId($team, 'football-data.org', '5');
        $team->addExternalId($externalId);

        $this->teamRepository->save($team);
        $this->teamExternalIdRepository->save($externalId);
        $this->entityManager->clear();

        $found = $this->teamExternalIdRepository->findByProviderAndExternalId('football-data.org', '5');

        $this->assertNotNull($found);
        $this->assertSame('Bayern Munich', $found->getTeam()->getName());
    }
}
