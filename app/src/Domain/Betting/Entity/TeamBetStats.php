<?php

declare(strict_types=1);

namespace App\Domain\Betting\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'team_bet_stats')]
#[ORM\UniqueConstraint(name: 'uq_team_bet_stats', columns: ['team_id', 'bet_type', 'season'])]
class TeamBetStats
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private int $id;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Team $team;

    #[ORM\Column(length: 20)]
    private string $betType;

    #[ORM\Column(length: 7)]
    private string $season;

    #[ORM\Column(options: ['default' => 0])]
    private int $timesBet = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $timesWon = 0;

    #[ORM\Column(options: ['default' => 0])]
    private int $timesLost = 0;

    private function __construct(Team $team, string $betType, string $season)
    {
        $this->team    = $team;
        $this->betType = $betType;
        $this->season  = $season;
    }

    public static function create(Team $team, string $betType, string $season): self
    {
        return new self($team, $betType, $season);
    }

    public function id(): int { return $this->id; }
    public function team(): Team { return $this->team; }
    public function betType(): string { return $this->betType; }
    public function season(): string { return $this->season; }
    public function timesBet(): int { return $this->timesBet; }
    public function timesWon(): int { return $this->timesWon; }
    public function timesLost(): int { return $this->timesLost; }

    public function winRate(): float
    {
        if ($this->timesBet === 0) {
            return 0.0;
        }

        return round($this->timesWon / $this->timesBet * 100, 1);
    }

    public function recordWin(): void
    {
        $this->timesBet++;
        $this->timesWon++;
    }

    public function recordLoss(): void
    {
        $this->timesBet++;
        $this->timesLost++;
    }
}
