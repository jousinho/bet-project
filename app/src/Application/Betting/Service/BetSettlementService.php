<?php

declare(strict_types=1);

namespace App\Application\Betting\Service;

use App\Domain\Betting\Entity\Bet;
use App\Domain\Betting\Entity\TeamBetStats;
use App\Domain\Betting\Repository\BetRepositoryInterface;
use App\Domain\Tracking\Repository\FootballDataProviderInterface;
use App\Domain\Betting\Repository\TeamBetStatsRepositoryInterface;
use App\Domain\Betting\Service\SeasonResolver;

class BetSettlementService
{
    public function __construct(
        private readonly BetRepositoryInterface $betRepository,
        private readonly TeamBetStatsRepositoryInterface $statsRepository,
        private readonly FootballDataProviderInterface $footballDataProvider,
        private readonly SeasonResolver $seasonResolver,
    ) {}

    public function settleAll(): void
    {
        $now = new \DateTimeImmutable();
        $pendingBets = $this->betRepository->findPendingBefore($now);

        foreach ($pendingBets as $bet) {
            $this->settle($bet, $now);
        }
    }

    private function settle(Bet $bet, \DateTimeImmutable $now): void
    {
        $team = $bet->team();
        $matches = $this->footballDataProvider->getFinishedMatches(
            $this->findExternalId($team),
            $team->league(),
            20,
        );

        $match = $this->findMatchForFixture($matches, $bet->fixtureDate());
        if ($match === null) {
            return;
        }

        $won = $this->evaluateOutcome($bet->betType(), $match);
        $won ? $bet->markWon($now) : $bet->markLost($now);
        $this->betRepository->save($bet);

        $this->updateStats($bet, $won, $now);
    }

    private function findExternalId(\App\Domain\Tracking\Entity\Team $team): string
    {
        foreach ($team->externalIds() as $externalId) {
            if ($externalId->provider() === 'football-data.org') {
                return $externalId->externalId();
            }
        }

        return '';
    }

    private function findMatchForFixture(array $matches, \DateTimeImmutable $fixtureDate): ?array
    {
        foreach ($matches as $match) {
            $matchDate = new \DateTimeImmutable($match['date']);
            if (abs($matchDate->getTimestamp() - $fixtureDate->getTimestamp()) <= 86400) {
                return $match;
            }
        }

        return null;
    }

    private function evaluateOutcome(string $betType, array $match): bool
    {
        $total    = $match['goalsScored'] + $match['goalsAgainst'];
        $htTotal  = $match['halfTimeGoalsScored'] + $match['halfTimeGoalsAgainst'];
        $wonBothH = $match['halfTimeGoalsScored'] > $match['halfTimeGoalsAgainst'] && $match['result'] === 'W';

        return match ($betType) {
            Bet::TYPE_OVER_2_5        => $total >= 3,
            Bet::TYPE_HOME_WIN        => $match['isHome'] && $match['result'] === 'W',
            Bet::TYPE_OVER_1_5        => $total >= 2,
            Bet::TYPE_OVER_3_5        => $total >= 4,
            Bet::TYPE_UNDER_2_5       => $total < 3,
            Bet::TYPE_AWAY_WIN        => !$match['isHome'] && $match['result'] === 'W',
            Bet::TYPE_DOUBLE_CHANCE   => $match['isHome'] && $match['result'] !== 'L',
            Bet::TYPE_OVER_05_HT      => $htTotal >= 1,
            Bet::TYPE_WIN_BOTH_HALVES => $wonBothH,
            default                   => false,
        };
    }

    private function updateStats(Bet $bet, bool $won, \DateTimeImmutable $now): void
    {
        $season = $this->seasonResolver->resolve($bet->fixtureDate());
        $stats  = $this->statsRepository->findByTeamBetTypeSeason($bet->team(), $bet->betType(), $season)
            ?? TeamBetStats::create($bet->team(), $bet->betType(), $season);

        $won ? $stats->recordWin() : $stats->recordLoss();
        $this->statsRepository->save($stats);
    }
}
