<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Criterion;

use App\Domain\Betting\Criterion\HomeWinCriterion;
use App\Domain\Betting\Entity\Team;
use PHPUnit\Framework\TestCase;

class HomeWinCriterionTest extends TestCase
{
    private HomeWinCriterion $criterion;

    protected function setUp(): void
    {
        $this->criterion = new HomeWinCriterion();
    }

    private function homeTeam(string $homeForm, string $opponentForm): Team
    {
        $team = Team::create('Test', 'PD');
        $team->setNextFixtureDate(new \DateTimeImmutable('+1 day'));
        $team->setNextFixtureIsHome(true);
        $team->setFormLast5Home($homeForm);
        $team->setNextFixtureOpponentFormSituational($opponentForm);
        return $team;
    }

    public function test_isMet__strong_home_form_and_weak_opponent__should_return_true(): void
    {
        $team = $this->homeTeam(homeForm: 'WWWWL', opponentForm: 'LLLWD');
        $this->assertTrue($this->criterion->isMet($team));
    }

    public function test_isMet__home_team_with_only_3_wins__should_return_false(): void
    {
        $team = $this->homeTeam(homeForm: 'WWWLL', opponentForm: 'LLLWW');
        $this->assertFalse($this->criterion->isMet($team));
    }

    public function test_isMet__opponent_with_only_2_losses__should_return_false(): void
    {
        $team = $this->homeTeam(homeForm: 'WWWWW', opponentForm: 'LLWWW');
        $this->assertFalse($this->criterion->isMet($team));
    }

    public function test_isMet__away_team__should_return_false(): void
    {
        $team = Team::create('Test', 'PD');
        $team->setNextFixtureDate(new \DateTimeImmutable('+1 day'));
        $team->setNextFixtureIsHome(false);
        $team->setFormLast5Home('WWWWW');
        $team->setNextFixtureOpponentFormSituational('LLLLL');
        $this->assertFalse($this->criterion->isMet($team));
    }

    public function test_isMet__team_with_no_fixture__should_return_false(): void
    {
        $team = Team::create('Test', 'PD');
        $this->assertFalse($this->criterion->isMet($team));
    }
}
