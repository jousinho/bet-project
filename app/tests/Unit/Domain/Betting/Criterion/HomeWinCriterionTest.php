<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Criterion;

use App\Domain\Betting\Criterion\HomeWinCriterion;
use App\Domain\Betting\ValueObject\TeamSnapshot;
use PHPUnit\Framework\TestCase;

class HomeWinCriterionTest extends TestCase
{
    private HomeWinCriterion $criterion;

    protected function setUp(): void
    {
        $this->criterion = new HomeWinCriterion();
    }

    private function snapshot(array $overrides = []): TeamSnapshot
    {
        return new TeamSnapshot(
            teamId: 1,
            teamName: 'Test',
            league: 'PD',
            formLast8: null,
            formLast5Home: $overrides['formLast5Home'] ?? null,
            formLast5Away: null,
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
            nextFixtureIsHome: $overrides['nextFixtureIsHome'] ?? null,
            nextFixtureOpponentFormSituational: $overrides['nextFixtureOpponentFormSituational'] ?? null,
            nextFixtureOpponentId: null,
        );
    }

    public function test_isMet__strong_home_form_and_weak_opponent__should_return_true(): void
    {
        $snapshot = $this->snapshot([
            'nextFixtureIsHome' => true,
            'formLast5Home' => 'WWWWL',
            'nextFixtureOpponentFormSituational' => 'LLLWD',
        ]);
        $this->assertTrue($this->criterion->isMet($snapshot));
    }

    public function test_isMet__home_team_with_only_3_wins__should_return_false(): void
    {
        $snapshot = $this->snapshot([
            'nextFixtureIsHome' => true,
            'formLast5Home' => 'WWWLL',
            'nextFixtureOpponentFormSituational' => 'LLLWW',
        ]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__opponent_with_only_2_losses__should_return_false(): void
    {
        $snapshot = $this->snapshot([
            'nextFixtureIsHome' => true,
            'formLast5Home' => 'WWWWW',
            'nextFixtureOpponentFormSituational' => 'LLWWW',
        ]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__away_team__should_return_false(): void
    {
        $snapshot = $this->snapshot([
            'nextFixtureIsHome' => false,
            'formLast5Home' => 'WWWWW',
            'nextFixtureOpponentFormSituational' => 'LLLLL',
        ]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }

    public function test_isMet__team_with_no_fixture__should_return_false(): void
    {
        $snapshot = $this->snapshot(['nextFixtureDate' => null]);
        $this->assertFalse($this->criterion->isMet($snapshot));
    }
}
