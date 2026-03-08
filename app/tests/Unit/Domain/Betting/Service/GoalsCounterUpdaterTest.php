<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Service;

use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Service\GoalsCounterUpdater;
use PHPUnit\Framework\TestCase;

class GoalsCounterUpdaterTest extends TestCase
{
    private GoalsCounterUpdater $updater;
    private Team $team;

    protected function setUp(): void
    {
        $this->updater = new GoalsCounterUpdater();
        $this->team = Team::create('Test Team', 'PD');
    }

    private function homeMatch(int $goalsScored): array
    {
        return ['isHome' => true, 'goalsScored' => $goalsScored, 'goalsAgainst' => 0, 'result' => 'W', 'date' => '2025-01-01'];
    }

    private function awayMatch(int $goalsScored): array
    {
        return ['isHome' => false, 'goalsScored' => $goalsScored, 'goalsAgainst' => 0, 'result' => 'W', 'date' => '2025-01-01'];
    }

    public function test_updating_home_counters__when_team_scores_3_goals__should_increment_over25(): void
    {
        $this->updater->update([$this->homeMatch(3)], $this->team);

        $this->assertSame(1, $this->team->over25Home());
        $this->assertSame(1, $this->team->matchesPlayedHome());
    }

    public function test_updating_home_counters__when_team_scores_2_goals__should_not_increment_over25(): void
    {
        $this->updater->update([$this->homeMatch(2)], $this->team);

        $this->assertSame(0, $this->team->over25Home());
        $this->assertSame(1, $this->team->matchesPlayedHome());
    }

    public function test_updating_away_counters__when_team_scores_2_goals__should_increment_over15(): void
    {
        $this->updater->update([$this->awayMatch(2)], $this->team);

        $this->assertSame(1, $this->team->over15Away());
        $this->assertSame(1, $this->team->matchesPlayedAway());
    }

    public function test_updating_away_counters__when_team_scores_1_goal__should_not_increment_over15(): void
    {
        $this->updater->update([$this->awayMatch(1)], $this->team);

        $this->assertSame(0, $this->team->over15Away());
        $this->assertSame(1, $this->team->matchesPlayedAway());
    }
}
