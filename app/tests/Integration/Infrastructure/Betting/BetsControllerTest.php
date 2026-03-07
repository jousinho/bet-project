<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Betting;

use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Repository\TeamRepositoryInterface;
use App\Tests\Integration\WebIntegrationTestCase;

class BetsControllerTest extends WebIntegrationTestCase
{
    private TeamRepositoryInterface $teamRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teamRepository = static::getContainer()->get(TeamRepositoryInterface::class);
    }

    private function createTeam(string $name, \DateTimeImmutable $fixtureDate, bool $isHome = true): Team
    {
        $team = new Team($name, 'PD');
        $team->setNextFixtureDate($fixtureDate);
        $team->setNextFixtureIsHome($isHome);
        $this->teamRepository->save($team);

        return $team;
    }

    public function test_loading_bets_page__should_return_200(): void
    {
        $this->client->request('GET', '/tomorrow/bets');

        $this->assertResponseIsSuccessful();
    }

    public function test_loading_bets_page__should_render_all_tracked_teams(): void
    {
        $this->createTeam('Real Madrid', new \DateTimeImmutable('+7 days'));
        $this->createTeam('FC Barcelona', new \DateTimeImmutable('+8 days'));

        $this->client->request('GET', '/tomorrow/bets');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('Real Madrid', $content);
        $this->assertStringContainsString('FC Barcelona', $content);
    }

    public function test_loading_bets_page__when_team_plays_tomorrow__should_be_highlighted_in_response(): void
    {
        $this->createTeam('Real Madrid', new \DateTimeImmutable('tomorrow noon'));

        $this->client->request('GET', '/tomorrow/bets');

        $content = $this->client->getResponse()->getContent();
        $this->assertStringContainsString('TOMORROW', $content);
        $this->assertStringContainsString('highlighted', $content);
    }

    public function test_loading_bets_page__teams_should_be_ordered_by_next_fixture_date(): void
    {
        $this->createTeam('FC Barcelona', new \DateTimeImmutable('+10 days'));
        $this->createTeam('Real Madrid', new \DateTimeImmutable('+3 days'));

        $this->client->request('GET', '/tomorrow/bets');

        $content = $this->client->getResponse()->getContent();
        $positionMadrid = strpos($content, 'Real Madrid');
        $positionBarca = strpos($content, 'FC Barcelona');
        $this->assertLessThan($positionBarca, $positionMadrid);
    }
}
