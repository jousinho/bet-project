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

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $nextFixtureOpponentName = null;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $nextFixtureOpponentFormSituational = null;

    #[ORM\Column(nullable: true)]
    private ?bool $nextFixtureIsHome = null;

    #[ORM\Column(nullable: true)]
    private ?int $nextFixtureMatchday = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSyncedAt = null;

    #[ORM\OneToMany(mappedBy: 'team', targetEntity: TeamExternalId::class, cascade: ['persist', 'remove'])]
    private Collection $externalIds;

    private function __construct(string $name, string $league)
    {
        $this->name = $name;
        $this->league = $league;
        $this->externalIds = new ArrayCollection();
    }

    public static function create(string $name, string $league): self
    {
        return new self($name, $league);
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function league(): string
    {
        return $this->league;
    }

    public function formLast8(): ?string
    {
        return $this->formLast8;
    }

    public function setFormLast8(?string $formLast8): void
    {
        $this->formLast8 = $formLast8;
    }

    public function formLast5Home(): ?string
    {
        return $this->formLast5Home;
    }

    public function setFormLast5Home(?string $formLast5Home): void
    {
        $this->formLast5Home = $formLast5Home;
    }

    public function formLast5Away(): ?string
    {
        return $this->formLast5Away;
    }

    public function setFormLast5Away(?string $formLast5Away): void
    {
        $this->formLast5Away = $formLast5Away;
    }

    public function over25Home(): int
    {
        return $this->over25Home;
    }

    public function setOver25Home(int $over25Home): void
    {
        $this->over25Home = $over25Home;
    }

    public function matchesPlayedHome(): int
    {
        return $this->matchesPlayedHome;
    }

    public function setMatchesPlayedHome(int $matchesPlayedHome): void
    {
        $this->matchesPlayedHome = $matchesPlayedHome;
    }

    public function over15Away(): int
    {
        return $this->over15Away;
    }

    public function setOver15Away(int $over15Away): void
    {
        $this->over15Away = $over15Away;
    }

    public function matchesPlayedAway(): int
    {
        return $this->matchesPlayedAway;
    }

    public function setMatchesPlayedAway(int $matchesPlayedAway): void
    {
        $this->matchesPlayedAway = $matchesPlayedAway;
    }

    public function nextFixtureDate(): ?\DateTimeImmutable
    {
        return $this->nextFixtureDate;
    }

    public function setNextFixtureDate(?\DateTimeImmutable $nextFixtureDate): void
    {
        $this->nextFixtureDate = $nextFixtureDate;
    }

    public function nextFixtureOpponentId(): ?int
    {
        return $this->nextFixtureOpponentId;
    }

    public function setNextFixtureOpponentId(?int $nextFixtureOpponentId): void
    {
        $this->nextFixtureOpponentId = $nextFixtureOpponentId;
    }

    public function nextFixtureOpponentName(): ?string
    {
        return $this->nextFixtureOpponentName;
    }

    public function setNextFixtureOpponentName(?string $nextFixtureOpponentName): void
    {
        $this->nextFixtureOpponentName = $nextFixtureOpponentName;
    }

    public function nextFixtureOpponentFormSituational(): ?string
    {
        return $this->nextFixtureOpponentFormSituational;
    }

    public function setNextFixtureOpponentFormSituational(?string $form): void
    {
        $this->nextFixtureOpponentFormSituational = $form;
    }

    public function nextFixtureIsHome(): ?bool
    {
        return $this->nextFixtureIsHome;
    }

    public function setNextFixtureIsHome(?bool $nextFixtureIsHome): void
    {
        $this->nextFixtureIsHome = $nextFixtureIsHome;
    }

    public function nextFixtureMatchday(): ?int
    {
        return $this->nextFixtureMatchday;
    }

    public function setNextFixtureMatchday(?int $matchday): void
    {
        $this->nextFixtureMatchday = $matchday;
    }

    public function lastSyncedAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncedAt;
    }

    public function setLastSyncedAt(?\DateTimeImmutable $lastSyncedAt): void
    {
        $this->lastSyncedAt = $lastSyncedAt;
    }

    public function externalIds(): Collection
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
