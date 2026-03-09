<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Criterion;

use App\Domain\Betting\Criterion\Under25Criterion;
use App\Domain\Betting\ValueObject\TeamSnapshot;
use PHPUnit\Framework\TestCase;

class Under25CriterionTest extends TestCase
{
    private Under25Criterion $criterion;

    protected function setUp(): void
    {
        $this->criterion = new Under25Criterion();
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
            over25Home: $overrides['over25Home'] ?? 0,
            over15Home: 0,
            over35Home: 0,
            over05HtHome: 0,
            winBothHalvesHome: 0,
            matchesPlayedHome: $overrides['matchesPlayedHome'] ?? 0,
            over15Away: 0,
            over25Away: $overrides['over25Away'] ?? 0,
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

    public function test_isMet__home_team_with_low_over25_ratio__should_return_true(): void
    {
        // 3 over25 out of 10 = 30% over25, 70% under25 => meets 0.60 threshold
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over25Home' => 3, 'matchesPlayedHome' => 10]);
        $this->assertTrue($this->criterion->isMet($snapshot));
    }

    public function test_isMet__home_team_with_high_over25_ratio__should_return_false(): void
    {
        // 5 over25 out of 10 = 50% under25 => does not meet 0.60 threshold
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over25Home' => 5, 'matchesPlayedHome' => 10]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__home_team_with_insufficient_matches__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over25Home' => 0, 'matchesPlayedHome' => 4]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team_with_low_over25_ratio__should_return_true(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => false, 'over25Away' => 3, 'matchesPlayedAway' => 10]);
        $this->assertTrue($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team_with_high_over25_ratio__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => false, 'over25Away' => 5, 'matchesPlayedAway' => 10]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__team_with_no_fixture__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureDate' => null]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }
}
