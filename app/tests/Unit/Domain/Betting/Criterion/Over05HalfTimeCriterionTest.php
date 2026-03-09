<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Criterion;

use App\Domain\Betting\Criterion\Over05HalfTimeCriterion;
use App\Domain\Betting\ValueObject\TeamSnapshot;
use PHPUnit\Framework\TestCase;

class Over05HalfTimeCriterionTest extends TestCase
{
    private Over05HalfTimeCriterion $criterion;

    protected function setUp(): void
    {
        $this->criterion = new Over05HalfTimeCriterion();
    }

    private function snapshot(array $overrides = []): TeamSnapshot
    {
        return new TeamSnapshot(
            teamId: 1,
            teamName: 'Test',
            league: 'PD',
            formLast8: null,
            formLast5Home: null,
            formLast5Away: null,
            over25Home: 0,
            over15Home: 0,
            over35Home: 0,
            over05HtHome: $overrides['over05HtHome'] ?? 0,
            winBothHalvesHome: 0,
            matchesPlayedHome: $overrides['matchesPlayedHome'] ?? 0,
            over15Away: 0,
            over25Away: 0,
            over35Away: 0,
            over05HtAway: $overrides['over05HtAway'] ?? 0,
            winBothHalvesAway: 0,
            matchesPlayedAway: $overrides['matchesPlayedAway'] ?? 0,
            nextFixtureDate: $overrides['nextFixtureDate'] ?? new \DateTimeImmutable('+1 day'),
            nextFixtureMatchday: null,
            nextFixtureOpponentName: null,
            nextFixtureIsHome: $overrides['nextFixtureIsHome'] ?? null,
            nextFixtureOpponentFormSituational: null,
            nextFixtureOpponentId: null,
        );
    }

    public function test_isMet__home_team_with_70_percent_ht_ratio__should_return_true(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over05HtHome' => 7, 'matchesPlayedHome' => 10]);
        $this->assertTrue($this->criterion->isMet($snapshot));
    }

    public function test_isMet__home_team_below_70_percent__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over05HtHome' => 6, 'matchesPlayedHome' => 10]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__home_team_with_insufficient_matches__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over05HtHome' => 4, 'matchesPlayedHome' => 4]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team_with_70_percent_ht_ratio__should_return_true(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => false, 'over05HtAway' => 7, 'matchesPlayedAway' => 10]);
        $this->assertTrue($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team_below_70_percent__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => false, 'over05HtAway' => 6, 'matchesPlayedAway' => 10]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__team_with_no_fixture__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureDate' => null]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }
}
