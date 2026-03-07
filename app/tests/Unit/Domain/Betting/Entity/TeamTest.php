<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Entity;

use App\Domain\Betting\Entity\Team;
use PHPUnit\Framework\TestCase;

class TeamTest extends TestCase
{
    public function test_team_entity__when_created__should_have_correct_default_values(): void
    {
        $team = new Team('Real Madrid', 'PD');

        $this->assertSame('Real Madrid', $team->getName());
        $this->assertSame('PD', $team->getLeague());
        $this->assertNull($team->getFormLast8());
        $this->assertNull($team->getFormLast5Home());
        $this->assertNull($team->getFormLast5Away());
        $this->assertSame(0, $team->getOver25Home());
        $this->assertSame(0, $team->getMatchesPlayedHome());
        $this->assertSame(0, $team->getOver15Away());
        $this->assertSame(0, $team->getMatchesPlayedAway());
        $this->assertNull($team->getNextFixtureDate());
        $this->assertNull($team->getNextFixtureOpponentId());
        $this->assertNull($team->getNextFixtureIsHome());
        $this->assertNull($team->getLastSyncedAt());
        $this->assertCount(0, $team->getExternalIds());
    }
}
