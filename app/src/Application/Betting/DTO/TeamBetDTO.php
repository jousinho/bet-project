<?php

declare(strict_types=1);

namespace App\Application\Betting\DTO;

readonly class TeamBetDTO
{
    public function __construct(
        public string  $teamName,
        public string  $nextFixtureDate,
        public ?string $nextFixtureOpponentName,
        public bool    $isHome,
        public bool    $highlightedTomorrow,

        public ?string $formLast8,
        public ?string $formSituational,
        public ?string $opponentFormSituational,

        public int     $teamOverCount,
        public int     $teamMatchesPlayed,
        public int     $opponentOverCount,
        public int     $opponentMatchesPlayed,

        /** @var string[] */
        public array   $activeBetTypes = [],
    ) {}
}
