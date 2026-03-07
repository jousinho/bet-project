<?php

declare(strict_types=1);

namespace App\Domain\Betting\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'team_external_id')]
class TeamExternalId
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'externalIds')]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\Column(length: 50)]
    private string $provider;

    #[ORM\Column(length: 50)]
    private string $externalId;

    public function __construct(Team $team, string $provider, string $externalId)
    {
        $this->team = $team;
        $this->provider = $provider;
        $this->externalId = $externalId;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getTeam(): Team
    {
        return $this->team;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }
}
