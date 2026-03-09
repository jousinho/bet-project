<?php

declare(strict_types=1);

namespace App\Domain\Betting\Entity;

use App\Domain\Tracking\Entity\Team;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'bet')]
#[ORM\UniqueConstraint(name: 'uq_bet_team_fixture_type', columns: ['team_id', 'fixture_date', 'bet_type'])]
class Bet
{
    public const TYPE_OVER_2_5        = 'over_2_5';
    public const TYPE_HOME_WIN        = 'home_win';
    public const TYPE_OVER_1_5        = 'over_1_5';
    public const TYPE_OVER_3_5        = 'over_3_5';
    public const TYPE_UNDER_2_5       = 'under_2_5';
    public const TYPE_AWAY_WIN        = 'away_win';
    public const TYPE_DOUBLE_CHANCE   = 'double_chance';
    public const TYPE_OVER_05_HT      = 'over_05_ht';
    public const TYPE_WIN_BOTH_HALVES = 'win_both_halves';

    public const STATUS_PENDING = 'pending';
    public const STATUS_WON    = 'won';
    public const STATUS_LOST   = 'lost';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\Column]
    private \DateTimeImmutable $fixtureDate;

    #[ORM\Column(length: 100)]
    private string $opponentName;

    #[ORM\Column(length: 20)]
    private string $betType;

    #[ORM\Column(length: 10)]
    private string $status;

    #[ORM\Column(length: 7)]
    private string $season;

    #[ORM\Column(nullable: true)]
    private ?int $matchday = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $settledAt = null;

    private function __construct(
        Team $team,
        \DateTimeImmutable $fixtureDate,
        string $opponentName,
        string $betType,
        string $season,
        ?int $matchday = null,
    ) {
        $this->team        = $team;
        $this->fixtureDate = $fixtureDate;
        $this->opponentName = $opponentName;
        $this->betType     = $betType;
        $this->season      = $season;
        $this->matchday    = $matchday;
        $this->status      = self::STATUS_PENDING;
        $this->createdAt   = new \DateTimeImmutable();
    }

    public static function create(
        Team $team,
        \DateTimeImmutable $fixtureDate,
        string $opponentName,
        string $betType,
        string $season,
        ?int $matchday = null,
    ): self {
        return new self($team, $fixtureDate, $opponentName, $betType, $season, $matchday);
    }

    public function id(): int { return $this->id; }
    public function team(): Team { return $this->team; }
    public function fixtureDate(): \DateTimeImmutable { return $this->fixtureDate; }
    public function opponentName(): string { return $this->opponentName; }
    public function betType(): string { return $this->betType; }
    public function status(): string { return $this->status; }
    public function season(): string { return $this->season; }
    public function matchday(): ?int { return $this->matchday; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
    public function settledAt(): ?\DateTimeImmutable { return $this->settledAt; }

    public function markWon(\DateTimeImmutable $now): void
    {
        $this->status    = self::STATUS_WON;
        $this->settledAt = $now;
    }

    public function markLost(\DateTimeImmutable $now): void
    {
        $this->status    = self::STATUS_LOST;
        $this->settledAt = $now;
    }
}
