<?php

declare(strict_types=1);

namespace App\Infrastructure\Tracking\Http\Client;

use App\Domain\Tracking\Repository\FootballDataProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class FootballDataClient implements FootballDataProviderInterface
{
    private const BASE_URL = 'https://api.football-data.org/v4';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly string $apiKey,
    ) {}

    public function getNextFixture(string $externalTeamId, string $competition): array
    {
        $matches = $this->fetchMatches($externalTeamId, 'SCHEDULED', $competition, 1);

        if (empty($matches)) {
            return [];
        }

        $match = $matches[0];
        $isHome = (string) $match['homeTeam']['id'] === $externalTeamId;
        $opponent = $isHome ? $match['awayTeam'] : $match['homeTeam'];

        return [
            'date' => $match['utcDate'],
            'opponentExternalId' => (string) $opponent['id'],
            'opponentName' => $opponent['name'],
            'isHome' => $isHome,
            'matchday' => (int) ($match['matchday'] ?? 0),
        ];
    }

    public function getFinishedMatches(string $externalTeamId, string $competition, int $limit): array
    {
        $matches = $this->fetchMatches($externalTeamId, 'FINISHED', $competition, $limit);

        $result = [];
        foreach ($matches as $match) {
            $isHome = (string) $match['homeTeam']['id'] === $externalTeamId;
            $homeGoals = $match['score']['fullTime']['home'] ?? 0;
            $awayGoals = $match['score']['fullTime']['away'] ?? 0;

            $goalsScored = $isHome ? $homeGoals : $awayGoals;
            $goalsAgainst = $isHome ? $awayGoals : $homeGoals;

            $htHome = $match['score']['halfTime']['home'] ?? 0;
            $htAway = $match['score']['halfTime']['away'] ?? 0;
            $htGoalsScored  = $isHome ? $htHome : $htAway;
            $htGoalsAgainst = $isHome ? $htAway : $htHome;

            $result[] = [
                'date' => $match['utcDate'],
                'isHome' => $isHome,
                'goalsScored' => $goalsScored,
                'goalsAgainst' => $goalsAgainst,
                'result' => $this->calculateResult($goalsScored, $goalsAgainst),
                'halfTimeGoalsScored'  => $htGoalsScored,
                'halfTimeGoalsAgainst' => $htGoalsAgainst,
            ];
        }

        return $result;
    }

    private function fetchMatches(string $teamId, string $status, string $competition, int $limit): array
    {
        try {
            $response = $this->httpClient->request(
                'GET',
                sprintf('%s/teams/%s/matches', self::BASE_URL, $teamId),
                [
                    'headers' => ['X-Auth-Token' => $this->apiKey],
                    'query' => [
                        'status' => $status,
                        'competitions' => $competition,
                        'limit' => $limit,
                    ],
                ]
            );

            $data = $response->toArray();

            return $data['matches'] ?? [];
        } catch (\Throwable $e) {
            $this->logger->warning('FootballDataClient error: {message}', [
                'message' => $e->getMessage(),
                'teamId' => $teamId,
                'status' => $status,
            ]);

            return [];
        }
    }

    private function calculateResult(int $goalsScored, int $goalsAgainst): string
    {
        if ($goalsScored > $goalsAgainst) {
            return 'W';
        }

        if ($goalsScored < $goalsAgainst) {
            return 'L';
        }

        return 'D';
    }
}
