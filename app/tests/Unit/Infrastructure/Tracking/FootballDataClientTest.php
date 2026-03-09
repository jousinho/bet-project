<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Tracking;

use App\Infrastructure\Tracking\Http\Client\FootballDataClient;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class FootballDataClientTest extends TestCase
{
    private HttpClientInterface&MockObject $httpClient;
    private LoggerInterface $logger;
    private FootballDataClient $client;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->logger = $this->createStub(LoggerInterface::class);
        $this->client = new FootballDataClient($this->httpClient, $this->logger, 'test-api-key');
    }

    public function test_getting_finished_matches__should_return_expected_matches_array(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.football-data.org/v4/teams/86/matches', $this->callback(
                fn (array $opts) => $opts['headers']['X-Auth-Token'] === 'test-api-key'
                    && $opts['query']['status'] === 'FINISHED'
                    && $opts['query']['competitions'] === 'PD'
            ))
            ->willReturn($this->makeResponse([
                'matches' => [
                    $this->makeMatchData('86', '81', 2, 1, '2025-01-10T20:00:00Z'),
                    $this->makeMatchData('77', '86', 0, 1, '2025-01-03T20:00:00Z'),
                ],
            ]));

        $matches = $this->client->getFinishedMatches('86', 'PD', 5);

        $this->assertCount(2, $matches);

        // Primer partido: Real Madrid en casa, gana 2-1
        $this->assertTrue($matches[0]['isHome']);
        $this->assertSame(2, $matches[0]['goalsScored']);
        $this->assertSame(1, $matches[0]['goalsAgainst']);
        $this->assertSame('W', $matches[0]['result']);

        // Segundo partido: Real Madrid fuera, gana 0-1
        $this->assertFalse($matches[1]['isHome']);
        $this->assertSame(1, $matches[1]['goalsScored']);
        $this->assertSame(0, $matches[1]['goalsAgainst']);
        $this->assertSame('W', $matches[1]['result']);
    }

    public function test_getting_finished_matches__when_api_returns_empty__should_return_empty_array(): void
    {
        $this->httpClient->expects($this->once())->method('request')
            ->willReturn($this->makeResponse(['matches' => []]));

        $matches = $this->client->getFinishedMatches('86', 'PD', 5);

        $this->assertSame([], $matches);
    }

    public function test_getting_next_fixture__should_return_expected_fixture_data(): void
    {
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.football-data.org/v4/teams/86/matches', $this->callback(
                fn (array $opts) => $opts['query']['status'] === 'SCHEDULED'
            ))
            ->willReturn($this->makeResponse([
                'matches' => [
                    [
                        'utcDate' => '2025-03-15T20:00:00Z',
                        'homeTeam' => ['id' => 86, 'name' => 'Real Madrid'],
                        'awayTeam' => ['id' => 81, 'name' => 'FC Barcelona'],
                    ],
                ],
            ]));

        $fixture = $this->client->getNextFixture('86', 'PD');

        $this->assertSame('2025-03-15T20:00:00Z', $fixture['date']);
        $this->assertSame('81', $fixture['opponentExternalId']);
        $this->assertSame('FC Barcelona', $fixture['opponentName']);
        $this->assertTrue($fixture['isHome']);
    }

    public function test_api_client__when_rate_limit_exceeded__should_return_empty_array_without_exception(): void
    {
        $this->httpClient->expects($this->once())->method('request')
            ->willThrowException(new \RuntimeException('HTTP 429 Too Many Requests'));

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logger->expects($this->once())->method('warning');
        $this->client = new FootballDataClient($this->httpClient, $this->logger, 'test-api-key');

        $matches = $this->client->getFinishedMatches('86', 'PD', 5);

        $this->assertSame([], $matches);
    }

    private function makeResponse(array $data): ResponseInterface
    {
        $response = $this->createStub(ResponseInterface::class);
        $response->method('toArray')->willReturn($data);

        return $response;
    }

    private function makeMatchData(string $homeId, string $awayId, int $homeGoals, int $awayGoals, string $date): array
    {
        return [
            'utcDate' => $date,
            'homeTeam' => ['id' => (int) $homeId],
            'awayTeam' => ['id' => (int) $awayId],
            'score' => ['fullTime' => ['home' => $homeGoals, 'away' => $awayGoals]],
        ];
    }
}
