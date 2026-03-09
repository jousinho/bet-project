<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Criterion;

use App\Domain\Betting\Criterion\Over25Criterion;
use App\Domain\Betting\ValueObject\TeamSnapshot;
use PHPUnit\Framework\TestCase;

class Over25CriterionTest extends TestCase
{
    private Over25Criterion $criterion;

    protected function setUp(): void
    {
        $this->criterion = new Over25Criterion();
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
            matchesPlayedHome: $overrides['matchesPlayedHome'] ?? 0,
            over15Away: $overrides['over15Away'] ?? 0,
            matchesPlayedAway: $overrides['matchesPlayedAway'] ?? 0,
            nextFixtureDate: $overrides['nextFixtureDate'] ?? new \DateTimeImmutable('+1 day'),
            nextFixtureMatchday: null,
            nextFixtureOpponentName: null,
            nextFixtureIsHome: $overrides['nextFixtureIsHome'] ?? null,
            nextFixtureOpponentFormSituational: null,
            nextFixtureOpponentId: null,
        );
    }

    public function test_isMet__home_team_with_high_over25_ratio__should_return_true(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over25Home' => 6, 'matchesPlayedHome' => 8]);
        $this->assertTrue($this->criterion->isMet($snapshot));
    }

    public function test_isMet__home_team_with_low_over25_ratio__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over25Home' => 4, 'matchesPlayedHome' => 8]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__home_team_with_insufficient_matches__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'over25Home' => 3, 'matchesPlayedHome' => 4]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team_with_high_over15_ratio__should_return_true(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => false, 'over15Away' => 5, 'matchesPlayedAway' => 8]);
        $this->assertTrue($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team_with_low_over15_ratio__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => false, 'over15Away' => 3, 'matchesPlayedAway' => 8]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__team_with_no_fixture__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureDate' => null]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }
}
