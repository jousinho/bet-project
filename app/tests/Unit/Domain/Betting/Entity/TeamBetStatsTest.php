<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Entity;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Entity\TeamBetStats;
use PHPUnit\Framework\TestCase;

class TeamBetStatsTest extends TestCase
{
    private Team $team;

    protected function setUp(): void
    {
        $this->team = Team::create('Bayern Munich', 'BL1');
    }

    public function test_create__should_have_zero_counters(): void
    {
        $stats = TeamBetStats::create($this->team, Bet::TYPE_OVER_2_5, '2025/26');

        $this->assertSame(0, $stats->timesBet());
        $this->assertSame(0, $stats->timesWon());
        $this->assertSame(0, $stats->timesLost());
        $this->assertSame(0.0, $stats->winRate());
    }

    public function test_recordWin__should_increment_times_bet_and_times_won(): void
    {
        $stats = TeamBetStats::create($this->team, Bet::TYPE_OVER_2_5, '2025/26');

        $stats->recordWin();
        $stats->recordWin();

        $this->assertSame(2, $stats->timesBet());
        $this->assertSame(2, $stats->timesWon());
        $this->assertSame(0, $stats->timesLost());
    }

    public function test_recordLoss__should_increment_times_bet_and_times_lost(): void
    {
        $stats = TeamBetStats::create($this->team, Bet::TYPE_OVER_2_5, '2025/26');

        $stats->recordLoss();

        $this->assertSame(1, $stats->timesBet());
        $this->assertSame(0, $stats->timesWon());
        $this->assertSame(1, $stats->timesLost());
    }

    public function test_winRate__with_mixed_results__should_return_correct_percentage(): void
    {
        $stats = TeamBetStats::create($this->team, Bet::TYPE_OVER_2_5, '2025/26');

        $stats->recordWin();
        $stats->recordWin();
        $stats->recordWin();
        $stats->recordLoss();

        $this->assertSame(75.0, $stats->winRate());
    }

    public function test_winRate__with_no_bets__should_return_zero(): void
    {
        $stats = TeamBetStats::create($this->team, Bet::TYPE_OVER_2_5, '2025/26');

        $this->assertSame(0.0, $stats->winRate());
    }
}
