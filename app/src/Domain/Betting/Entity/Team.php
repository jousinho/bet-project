<?php

declare(strict_types=1);

namespace App\Domain\Betting\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'team')]
class Team
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 10)]
    private string $league;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $formLast8 = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $formLast5Home = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $formLast5Away = null;

    #[ORM\Column(options: ['default' => 0])]
    private int $over25Home = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $matchesPlayedHome = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $over15Away = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $matchesPlayedAway = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $nextFixtureDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $nextFixtureOpponentId = null;

    #[ORM\Column(nullable: true)]
    private ?bool $nextFixtureIsHome = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\OneToMany(mappedBy: 'team', targetEntity: TeamExternalId::class, cascade: ['persist', 'remove'])]
    private Collection $externalIds;

    public function __construct(string $name, string $league)
    {
        $this->name = $name;
        $this->league = $league;
        $this->externalIds = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLeague(): string
    {
        return $this->league;
    }

    public function getFormLast8(): ?string
    {
        return $this->formLast8;
    }

    public function setFormLast8(?string $formLast8): void
    {
        $this->formLast8 = $formLast8;
    }

    public function getFormLast5Home(): ?string
    {
        return $this->formLast5Home;
    }

    public function setFormLast5Home(?string $formLast5Home): void
    {
        $this->formLast5Home = $formLast5Home;
    }

    public function getFormLast5Away(): ?string
    {
        return $this->formLast5Away;
    }

    public function setFormLast5Away(?string $formLast5Away): void
    {
        $this->formLast5Away = $formLast5Away;
    }

    public function getOver25Home(): int
    {
        return $this->over25Home;
    }

    public function setOver25Home(int $over25Home): void
    {
        $this->over25Home = $over25Home;
    }

    public function getMatchesPlayedHome(): int
    {
        return $this->matchesPlayedHome;
    }

    public function setMatchesPlayedHome(int $matchesPlayedHome): void
    {
        $this->matchesPlayedHome = $matchesPlayedHome;
    }

    public function getOver15Away(): int
    {
        return $this->over15Away;
    }

    public function setOver15Away(int $over15Away): void
    {
        $this->over15Away = $over15Away;
    }

    public function getMatchesPlayedAway(): int
    {
        return $this->matchesPlayedAway;
    }

    public function setMatchesPlayedAway(int $matchesPlayedAway): void
    {
        $this->matchesPlayedAway = $matchesPlayedAway;
    }

    public function getNextFixtureDate(): ?\DateTimeImmutable
    {
        return $this->nextFixtureDate;
    }

    public function setNextFixtureDate(?\DateTimeImmutable $nextFixtureDate): void
    {
        $this->nextFixtureDate = $nextFixtureDate;
    }

    public function getNextFixtureOpponentId(): ?int
    {
        return $this->nextFixtureOpponentId;
    }

    public function setNextFixtureOpponentId(?int $nextFixtureOpponentId): void
    {
        $this->nextFixtureOpponentId = $nextFixtureOpponentId;
    }

    public function getNextFixtureIsHome(): ?bool
    {
        return $this->nextFixtureIsHome;
    }

    public function setNextFixtureIsHome(?bool $nextFixtureIsHome): void
    {
        $this->nextFixtureIsHome = $nextFixtureIsHome;
    }

    public function getLastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): void
    {
        $this->lastSyncedAt = $lastSyncedAt;
    }

    public function getExternalIds(): Collection
    {
        return $this->externalIds;
    }

    public function addExternalId(TeamExternalId $externalId): void
    {
        if (!$this->externalIds->contains($externalId)) {
            $this->externalIds->add($externalId);
        }
    }
}
