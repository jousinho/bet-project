<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Betting;

use App\Infrastructure\Betting\Http\Client\FootballDataClient;
use App\Tests\Integration\IntegrationTestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class FootballDataClientIntegrationTest extends IntegrationTestCase
{
    private function makeClient(MockHttpClient $httpClient): FootballDataClient
    {
        $logger = static::getContainer()->get('logger');

        return new FootballDataClient($httpClient, $logger, 'test-key');
    }

    public function test_getting_finished_matches_from_football_data__should_return_mapped_response(): void
    {
        $json = json_encode([
            'matches' => [
                [
                    'utcDate' => '2025-01-10T20:00:00Z',
                    'homeTeam' => ['id' => 86],
                    'awayTeam' => ['id' => 81],
                    'score' => ['fullTime' => ['home' => 3, 'away' => 1]],
                ],
                [
                    'utcDate' => '2025-01-03T20:00:00Z',
                    'homeTeam' => ['id' => 77],
                    'awayTeam' => ['id' => 86],
                    'score' => ['fullTime' => ['home' => 0, 'away' => 0]],
                ],
            ],
        ]);

        $httpClient = new MockHttpClient(new MockResponse($json));
        $client = $this->makeClient($httpClient);

        $matches = $client->getFinishedMatches('86', 'PD', 5);

        $this->assertCount(2, $matches);

        // Partido 1: local, gana 3-1
        $this->assertTrue($matches[0]['isHome']);
        $this->assertSame(3, $matches[0]['goalsScored']);
        $this->assertSame(1, $matches[0]['goalsAgainst']);
        $this->assertSame('W', $matches[0]['result']);

        // Partido 2: visitante, empate 0-0
        $this->assertFalse($matches[1]['isHome']);
        $this->assertSame(0, $matches[1]['goalsScored']);
        $this->assertSame(0, $matches[1]['goalsAgainst']);
        $this->assertSame('D', $matches[1]['result']);
    }

    public function test_getting_next_fixture_from_football_data__should_return_mapped_response(): void
    {
        $json = json_encode([
            'matches' => [
                [
                    'utcDate' => '2025-03-15T20:00:00Z',
                    'homeTeam' => ['id' => 81, 'name' => 'FC Barcelona'],
                    'awayTeam' => ['id' => 86, 'name' => 'Real Madrid'],
                ],
            ],
        ]);

        $httpClient = new MockHttpClient(new MockResponse($json));
        $client = $this->makeClient($httpClient);

        $fixture = $client->getNextFixture('86', 'PD');

        $this->assertSame('2025-03-15T20:00:00Z', $fixture['date']);
        $this->assertSame('81', $fixture['opponentExternalId']);
        $this->assertSame('FC Barcelona', $fixture['opponentName']);
        $this->assertFalse($fixture['isHome']); // 86 es visitante
    }
}
