<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Tracking\Service;

use App\Domain\Tracking\Entity\Team;
use App\Domain\Tracking\Service\GoalsCounterUpdater;
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

    private function match(bool $isHome, int $scored, int $against, int $htScored = 0, int $htAgainst = 0): array
    {
        return [
            'isHome'               => $isHome,
            'goalsScored'          => $scored,
            'goalsAgainst'         => $against,
            'halfTimeGoalsScored'  => $htScored,
            'halfTimeGoalsAgainst' => $htAgainst,
            'result'               => $scored > $against ? 'W' : ($scored < $against ? 'L' : 'D'),
            'date'                 => '2025-01-01',
        ];
    }

    public function test_updating_home_counters__when_team_scores_3_goals__should_increment_over25(): void
    {
        $this->updater->update([$this->match(true, 3, 0)], $this->team);

        $this->assertSame(1, $this->team->over25Home());
        $this->assertSame(1, $this->team->matchesPlayedHome());
    }

    public function test_updating_home_counters__when_team_scores_2_goals__should_not_increment_over25(): void
    {
        $this->updater->update([$this->match(true, 2, 0)], $this->team);

        $this->assertSame(0, $this->team->over25Home());
        $this->assertSame(1, $this->team->matchesPlayedHome());
    }

    public function test_updating_home_counters__with_2_total_goals__should_increment_over15_home(): void
    {
        $this->updater->update([$this->match(true, 1, 1)], $this->team);

        $this->assertSame(1, $this->team->over15Home());
    }

    public function test_updating_home_counters__with_4_total_goals__should_increment_over35_home(): void
    {
        $this->updater->update([$this->match(true, 3, 1)], $this->team);

        $this->assertSame(1, $this->team->over35Home());
        $this->assertSame(1, $this->team->over25Home());
        $this->assertSame(1, $this->team->over15Home());
    }

    public function test_updating_home_counters__with_ht_goal__should_increment_over05_ht_home(): void
    {
        $this->updater->update([$this->match(true, 2, 0, htScored: 1, htAgainst: 0)], $this->team);

        $this->assertSame(1, $this->team->over05HtHome());
    }

    public function test_updating_home_counters__without_ht_goal__should_not_increment_over05_ht_home(): void
    {
        $this->updater->update([$this->match(true, 1, 0, htScored: 0, htAgainst: 0)], $this->team);

        $this->assertSame(0, $this->team->over05HtHome());
    }

    public function test_updating_home_counters__when_team_wins_both_halves__should_increment_win_both_halves_home(): void
    {
        $this->updater->update([$this->match(true, 3, 1, htScored: 2, htAgainst: 0)], $this->team);

        $this->assertSame(1, $this->team->winBothHalvesHome());
    }

    public function test_updating_home_counters__when_team_loses_first_half__should_not_increment_win_both_halves_home(): void
    {
        $this->updater->update([$this->match(true, 2, 1, htScored: 0, htAgainst: 1)], $this->team);

        $this->assertSame(0, $this->team->winBothHalvesHome());
    }

    public function test_updating_away_counters__when_team_scores_2_goals__should_increment_over15(): void
    {
        $this->updater->update([$this->match(false, 2, 0)], $this->team);

        $this->assertSame(1, $this->team->over15Away());
        $this->assertSame(1, $this->team->matchesPlayedAway());
    }

    public function test_updating_away_counters__when_team_scores_1_goal__should_not_increment_over15(): void
    {
        $this->updater->update([$this->match(false, 1, 0)], $this->team);

        $this->assertSame(0, $this->team->over15Away());
        $this->assertSame(1, $this->team->matchesPlayedAway());
    }

    public function test_updating_away_counters__with_3_total_goals__should_increment_over25_away(): void
    {
        $this->updater->update([$this->match(false, 2, 1)], $this->team);

        $this->assertSame(1, $this->team->over25Away());
    }

    public function test_updating_away_counters__with_4_total_goals__should_increment_over35_away(): void
    {
        $this->updater->update([$this->match(false, 3, 1)], $this->team);

        $this->assertSame(1, $this->team->over35Away());
        $this->assertSame(1, $this->team->over25Away());
    }

    public function test_updating_away_counters__with_ht_goal__should_increment_over05_ht_away(): void
    {
        $this->updater->update([$this->match(false, 1, 0, htScored: 0, htAgainst: 1)], $this->team);

        $this->assertSame(1, $this->team->over05HtAway());
    }

    public function test_updating_away_counters__when_team_wins_both_halves__should_increment_win_both_halves_away(): void
    {
        $this->updater->update([$this->match(false, 2, 0, htScored: 1, htAgainst: 0)], $this->team);

        $this->assertSame(1, $this->team->winBothHalvesAway());
    }
}
