<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Criterion;

use App\Domain\Betting\Criterion\AwayWinCriterion;
use App\Domain\Betting\ValueObject\TeamSnapshot;
use PHPUnit\Framework\TestCase;

class AwayWinCriterionTest extends TestCase
{
    private AwayWinCriterion $criterion;

    protected function setUp(): void
    {
        $this->criterion = new AwayWinCriterion();
    }

    private function snapshot(array $overrides = []): TeamSnapshot
    {
        return new TeamSnapshot(
            teamId: 1,
            teamName: 'Test',
            league: 'PD',
            formLast8: null,
            formLast5Home: null,
            formLast5Away: $overrides['formLast5Away'] ?? null,
            over25Home: 0,
            over15Home: 0,
            over35Home: 0,
            over05HtHome: 0,
            winBothHalvesHome: 0,
            matchesPlayedHome: 0,
            over15Away: 0,
            over25Away: 0,
            over35Away: 0,
            over05HtAway: 0,
            winBothHalvesAway: 0,
            matchesPlayedAway: 0,
            nextFixtureDate: $overrides['nextFixtureDate'] ?? new \DateTimeImmutable('+1 day'),
            nextFixtureMatchday: null,
            nextFixtureOpponentName: null,
            nextFixtureIsHome: $overrides['nextFixtureIsHome'] ?? false,
            nextFixtureOpponentFormSituational: $overrides['opponentForm'] ?? null,
            nextFixtureOpponentId: null,
        );
    }

    public function test_isMet__away_team_with_3_away_wins_and_opponent_3_losses__should_return_true(): void
    {
        $snapshot = $this->snapshot(['formLast5Away' => 'WWWDL', 'opponentForm' => 'LLLWD']);
        $this->assertTrue($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team_with_only_2_away_wins__should_return_false(): void
    {
        $snapshot = $this->snapshot(['formLast5Away' => 'WWDLL', 'opponentForm' => 'LLLWD']);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team_but_opponent_has_only_2_losses__should_return_false(): void
    {
        $snapshot = $this->snapshot(['formLast5Away' => 'WWWDL', 'opponentForm' => 'LLWWW']);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__home_team__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureIsHome' => true, 'formLast5Away' => 'WWWWW', 'opponentForm' => 'LLLLL']);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__team_with_no_fixture__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureDate' => null]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }
}
