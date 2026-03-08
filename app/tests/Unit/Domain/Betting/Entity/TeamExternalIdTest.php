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
        $team = Team::create('Real Madrid', 'PD');
        $externalId = TeamExternalId::create($team, 'football-data.org', '86');

        $this->assertSame('football-data.org', $externalId->provider());
        $this->assertSame('86', $externalId->externalId());
        $this->assertSame($team, $externalId->team());
    }
}
