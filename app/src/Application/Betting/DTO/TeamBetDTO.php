<?php

declare(strict_types=1);

namespace App\Application\Betting\DTO;

readonly class TeamBetDTO
{
    public function __construct(
        // Match identity
        public string  $homeTeamName,
        public string  $awayTeamName,
        public string  $nextFixtureDate,
        public bool    $highlightedTomorrow,

        // Which teams in this match are tracked by us
        /** @var string[] */
        public array   $trackedTeamNames,

        // Home team stats
        public ?string $homeFormLast8,
        public ?string $homeFormSituational,
        public int     $homeOver25,
        public int     $homeOver15,
        public int     $homeOver35,
        public int     $homeMatchesPlayed,

        // Away team stats
        public ?string $awayFormSituational,
        public int     $awayOver15,
        public int     $awayOver25,
        public int     $awayOver35,
        public int     $awayMatchesPlayed,

        /** @var string[] */
        public array   $activeBetTypes = [],
    ) {}
}
