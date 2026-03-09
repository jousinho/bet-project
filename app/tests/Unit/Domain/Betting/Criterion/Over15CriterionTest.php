<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Criterion;

use App\Domain\Betting\Criterion\Over15Criterion;
use App\Domain\Betting\ValueObject\TeamSnapshot;
use PHPUnit\Framework\TestCase;

class Over15CriterionTest extends TestCase
{
    private Over15Criterion $criterion;

    protected function setUp(): void
    {
        $this->criterion = new Over15Criterion();
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
            over15Home: $overrides['over15Home'] ?? 0,
            over35Home: 0,
            over05HtHome: 0,
            winBothHalvesHome: 0,
            matchesPlayedHome: $overrides['matchesPlayedHome'] ?? 0,
            over15Away: $overrides['over15Away'] ?? 0,
            over25Away: 0,
            over35Away: 0,
            over05HtAway: 0,
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

    public function test_isMet__home_team_with_high_over15_ratio__should_return_true(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over15Home' => 7, 'matchesPlayedHome' => 10]);
        $this->assertTrue($this->criterion->isMet($snapshot));
    }

    public function test_isMet__home_team_with_low_over15_ratio__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over15Home' => 5, 'matchesPlayedHome' => 10]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__home_team_with_insufficient_matches__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over15Home' => 4, 'matchesPlayedHome' => 4]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team_with_high_over15_ratio__should_return_true(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => false, 'over15Away' => 7, 'matchesPlayedAway' => 10]);
        $this->assertTrue($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team_with_low_over15_ratio__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => false, 'over15Away' => 6, 'matchesPlayedAway' => 10]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__team_with_no_fixture__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureDate' => null]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }
}
