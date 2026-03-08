<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Entity;

use App\Domain\Betting\Entity\Team;
use PHPUnit\Framework\TestCase;

class TeamTest extends TestCase
{
    public function test_team_entity__when_created__should_have_correct_default_values(): void
    {
        $team = Team::create('Real Madrid', 'PD');

        $this->assertSame('Real Madrid', $team->name());
        $this->assertSame('PD', $team->league());
        $this->assertNull($team->formLast8());
        $this->assertNull($team->formLast5Home());
        $this->assertNull($team->formLast5Away());
        $this->assertSame(0, $team->over25Home());
        $this->assertSame(0, $team->matchesPlayedHome());
        $this->assertSame(0, $team->over15Away());
        $this->assertSame(0, $team->matchesPlayedAway());
        $this->assertNull($team->nextFixtureDate());
        $this->assertNull($team->nextFixtureOpponentId());
        $this->assertNull($team->nextFixtureIsHome());
        $this->assertNull($team->lastSyncedAt());
        $this->assertCount(0, $team->externalIds());
    }
}
