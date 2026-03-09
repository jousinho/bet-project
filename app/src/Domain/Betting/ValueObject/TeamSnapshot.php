<?php

declare(strict_types=1);

namespace App\Domain\Betting\ValueObject;

final class TeamSnapshot
{
    public function __construct(
        public readonly int $teamId,
        public readonly string $teamName,
        public readonly string $league,
        public readonly ?string $formLast8,
        public readonly ?string $formLast5Home,
        public readonly ?string $formLast5Away,
        public readonly int $over25Home,
        public readonly int $over15Home,
        public readonly int $over35Home,
        public readonly int $over05HtHome,
        public readonly int $winBothHalvesHome,
        public readonly int $matchesPlayedHome,
        public readonly int $over15Away,
        public readonly int $over25Away,
        public readonly int $over35Away,
        public readonly int $over05HtAway,
        public readonly int $winBothHalvesAway,
        public readonly int $matchesPlayedAway,
        public readonly ?\DateTimeImmutable $nextFixtureDate,
        public readonly ?int $nextFixtureMatchday,
        public readonly ?string $nextFixtureOpponentName,
        public readonly ?bool $nextFixtureIsHome,
        public readonly ?string $nextFixtureOpponentFormSituational,
        public readonly ?int $nextFixtureOpponentId,
    ) {}
}
