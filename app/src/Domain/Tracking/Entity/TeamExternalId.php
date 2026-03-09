<?php

declare(strict_types=1);

namespace App\Domain\Tracking\Entity;

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

    private function __construct(Team $team, string $provider, string $externalId)
    {
        $this->team = $team;
        $this->provider = $provider;
        $this->externalId = $externalId;
    }

    public static function create(Team $team, string $provider, string $externalId): self
    {
        return new self($team, $provider, $externalId);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function team(): Team
    {
        return $this->team;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function externalId(): string
    {
        return $this->externalId;
    }
}
