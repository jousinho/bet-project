<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Entity;

use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Entity\TeamExternalId;
use PHPUnit\Framework\TestCase;

class TeamExternalIdTest extends TestCase
{
    public function test_team_external_id__when_provider_is_set__should_return_correct_provider(): void
    {
        $team = new Team('Real Madrid', 'PD');
        $externalId = new TeamExternalId($team, 'football-data.org', '86');

        $this->assertSame('football-data.org', $externalId->getProvider());
        $this->assertSame('86', $externalId->getExternalId());
        $this->assertSame($team, $externalId->getTeam());
    }
}
