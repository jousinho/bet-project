<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Tracking;

use App\Domain\Tracking\Entity\Team;
use App\Domain\Tracking\Entity\TeamExternalId;
use App\Domain\Tracking\Repository\TeamExternalIdRepositoryInterface;
use App\Domain\Tracking\Repository\TeamRepositoryInterface;
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
        $team = Team::create('Real Madrid', 'PD');
        $this->teamRepository->save($team);
        $this->entityManager->clear();

        $found = $this->teamRepository->findById($team->id());

        $this->assertNotNull($found);
        $this->assertSame('Real Madrid', $found->name());
        $this->assertSame('PD', $found->league());
    }

    public function test_persisting_team_external_id__should_be_linked_to_team(): void
    {
        $team = Team::create('FC Barcelona', 'PD');
        $externalId = TeamExternalId::create($team, 'football-data.org', '81');
        $team->addExternalId($externalId);

        $this->teamRepository->save($team);
        $this->teamExternalIdRepository->save($externalId);
        $this->entityManager->clear();

        $found = $this->teamExternalIdRepository->findByProviderAndExternalId('football-data.org', '81');

        $this->assertNotNull($found);
        $this->assertSame('football-data.org', $found->provider());
        $this->assertSame('FC Barcelona', $found->team()->name());
    }

    public function test_finding_team_by_external_id_and_provider__should_return_correct_team(): void
    {
        $team = Team::create('Bayern Munich', 'BL1');
        $externalId = TeamExternalId::create($team, 'football-data.org', '5');
        $team->addExternalId($externalId);

        $this->teamRepository->save($team);
        $this->teamExternalIdRepository->save($externalId);
        $this->entityManager->clear();

        $found = $this->teamExternalIdRepository->findByProviderAndExternalId('football-data.org', '5');

        $this->assertNotNull($found);
        $this->assertSame('Bayern Munich', $found->team()->name());
    }
}
