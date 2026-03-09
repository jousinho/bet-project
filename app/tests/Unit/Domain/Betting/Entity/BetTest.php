<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Entity;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\Entity\Team;
use PHPUnit\Framework\TestCase;

class BetTest extends TestCase
{
    private Team $team;

    protected function setUp(): void
    {
        $this->team = Team::create('Real Madrid', 'PD');
    }

    public function test_create__should_have_pending_status(): void
    {
        $bet = Bet::create(
            $this->team,
            new \DateTimeImmutable('2026-03-14 20:00:00'),
            'FC Barcelona',
            Bet::TYPE_OVER_2_5,
            '2025/26',
        );

        $this->assertSame(Bet::STATUS_PENDING, $bet->status());
        $this->assertNull($bet->settledAt());
        $this->assertSame(Bet::TYPE_OVER_2_5, $bet->betType());
        $this->assertSame('2025/26', $bet->season());
        $this->assertSame('FC Barcelona', $bet->opponentName());
    }

    public function test_markWon__should_set_status_won_and_settled_at(): void
    {
        $bet = Bet::create($this->team, new \DateTimeImmutable(), 'Rival', Bet::TYPE_HOME_WIN, '2025/26');
        $now = new \DateTimeImmutable();

        $bet->markWon($now);

        $this->assertSame(Bet::STATUS_WON, $bet->status());
        $this->assertSame($now, $bet->settledAt());
    }

    public function test_markLost__should_set_status_lost_and_settled_at(): void
    {
        $bet = Bet::create($this->team, new \DateTimeImmutable(), 'Rival', Bet::TYPE_OVER_2_5, '2025/26');
        $now = new \DateTimeImmutable();

        $bet->markLost($now);

        $this->assertSame(Bet::STATUS_LOST, $bet->status());
        $this->assertSame($now, $bet->settledAt());
    }
}
