<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Criterion;

use App\Domain\Betting\Criterion\Over25Criterion;
use App\Domain\Betting\Entity\Team;
use PHPUnit\Framework\TestCase;

class Over25CriterionTest extends TestCase
{
    private Over25Criterion $criterion;

    protected function setUp(): void
    {
        $this->criterion = new Over25Criterion();
    }

    private function teamPlayingHome(int $over25Home, int $matchesPlayedHome): Team
    {
        $team = Team::create('Test', 'PD');
        $team->setNextFixtureDate(new \DateTimeImmutable('+1 day'));
        $team->setNextFixtureIsHome(true);
        $team->setOver25Home($over25Home);
        $team->setMatchesPlayedHome($matchesPlayedHome);
        return $team;
    }

    private function teamPlayingAway(int $over15Away, int $matchesPlayedAway): Team
    {
        $team = Team::create('Test', 'PD');
        $team->setNextFixtureDate(new \DateTimeImmutable('+1 day'));
        $team->setNextFixtureIsHome(false);
        $team->setOver15Away($over15Away);
        $team->setMatchesPlayedAway($matchesPlayedAway);
        return $team;
    }

    public function test_isMet__home_team_with_high_over25_ratio__should_return_true(): void
    {
        $team = $this->teamPlayingHome(over25Home: 6, matchesPlayedHome: 8);
        $this->assertTrue($this->criterion->isMet($team));
    }

    public function test_isMet__home_team_with_low_over25_ratio__should_return_false(): void
    {
        $team = $this->teamPlayingHome(over25Home: 4, matchesPlayedHome: 8);
        $this->assertFalse($this->criterion->isMet($team));
    }

    public function test_isMet__home_team_with_insufficient_matches__should_return_false(): void
    {
        $team = $this->teamPlayingHome(over25Home: 3, matchesPlayedHome: 4);
        $this->assertFalse($this->criterion->isMet($team));
    }

    public function test_isMet__away_team_with_high_over15_ratio__should_return_true(): void
    {
        $team = $this->teamPlayingAway(over15Away: 5, matchesPlayedAway: 8);
        $this->assertTrue($this->criterion->isMet($team));
    }

    public function test_isMet__away_team_with_low_over15_ratio__should_return_false(): void
    {
        $team = $this->teamPlayingAway(over15Away: 3, matchesPlayedAway: 8);
        $this->assertFalse($this->criterion->isMet($team));
    }

    public function test_isMet__team_with_no_fixture__should_return_false(): void
    {
        $team = Team::create('Test', 'PD');
        $this->assertFalse($this->criterion->isMet($team));
    }
}
