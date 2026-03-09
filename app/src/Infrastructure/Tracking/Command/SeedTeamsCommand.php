<?php

declare(strict_types=1);

namespace App\Infrastructure\Tracking\Command;

use App\Domain\Tracking\Entity\Team;
use App\Domain\Tracking\Entity\TeamExternalId;
use App\Domain\Tracking\Repository\TeamRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:teams:seed', description: 'Seeds the tracked teams into the database')]
class SeedTeamsCommand extends Command
{
    private const TEAMS = [
        // La Liga
        ['name' => 'Real Madrid CF',              'league' => 'PD',  'external_id' => '86'],
        ['name' => 'FC Barcelona',                'league' => 'PD',  'external_id' => '81'],
        ['name' => 'Club Atlético de Madrid',     'league' => 'PD',  'external_id' => '78'],

        // Premier League
        ['name' => 'Arsenal FC',                  'league' => 'PL',  'external_id' => '57'],
        ['name' => 'Chelsea FC',                  'league' => 'PL',  'external_id' => '61'],
        ['name' => 'Liverpool FC',                'league' => 'PL',  'external_id' => '64'],
        ['name' => 'Manchester City FC',          'league' => 'PL',  'external_id' => '65'],
        ['name' => 'Manchester United FC',        'league' => 'PL',  'external_id' => '66'],

        // Bundesliga
        ['name' => 'Bayer 04 Leverkusen',         'league' => 'BL1', 'external_id' => '3'],
        ['name' => 'Borussia Dortmund',           'league' => 'BL1', 'external_id' => '4'],
        ['name' => 'FC Bayern München',           'league' => 'BL1', 'external_id' => '5'],

        // Ligue 1
        ['name' => 'Olympique Lyonnais',          'league' => 'FL1', 'external_id' => '523'],
        ['name' => 'Paris Saint-Germain FC',      'league' => 'FL1', 'external_id' => '524'],
        ['name' => 'AS Monaco FC',                'league' => 'FL1', 'external_id' => '548'],

        // Serie A
        ['name' => 'AC Milan',                    'league' => 'SA',  'external_id' => '98'],
        ['name' => 'AS Roma',                     'league' => 'SA',  'external_id' => '100'],
        ['name' => 'Atalanta BC',                 'league' => 'SA',  'external_id' => '102'],
        ['name' => 'FC Internazionale Milano',    'league' => 'SA',  'external_id' => '108'],
        ['name' => 'Juventus FC',                 'league' => 'SA',  'external_id' => '109'],
        ['name' => 'SSC Napoli',                  'league' => 'SA',  'external_id' => '113'],
        ['name' => 'Como 1907',                   'league' => 'SA',  'external_id' => '7397'],

        // Eredivisie
        ['name' => 'PSV',                         'league' => 'DED', 'external_id' => '674'],
        ['name' => 'AFC Ajax',                    'league' => 'DED', 'external_id' => '678'],
    ];

    public function __construct(
        private readonly TeamRepositoryInterface $teamRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        foreach (self::TEAMS as $data) {
            $existing = $this->entityManager->getRepository(Team::class)->findOneBy(['name' => $data['name']]);

            if ($existing) {
                $io->note(sprintf('Team "%s" already exists, skipping.', $data['name']));
                continue;
            }

            $team = Team::create($data['name'], $data['league']);
            $externalId = TeamExternalId::create($team, 'football-data.org', $data['external_id']);
            $team->addExternalId($externalId);

            $this->entityManager->persist($team);
            $this->entityManager->persist($externalId);

            $io->text(sprintf('Created "%s" (league: %s, external_id: %s)', $data['name'], $data['league'], $data['external_id']));
        }

        $this->entityManager->flush();
        $io->success('Teams seeded successfully.');

        return Command::SUCCESS;
    }
}
