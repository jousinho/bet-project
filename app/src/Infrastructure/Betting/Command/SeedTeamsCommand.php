<?php

declare(strict_types=1);

namespace App\Infrastructure\Betting\Command;

use App\Domain\Betting\Entity\Team;
use App\Domain\Betting\Entity\TeamExternalId;
use App\Domain\Betting\Repository\TeamRepositoryInterface;
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
        ['name' => 'Real Madrid',       'league' => 'PD',  'external_id' => '86'],
        ['name' => 'FC Barcelona',      'league' => 'PD',  'external_id' => '81'],
        ['name' => 'Bayern Munich',     'league' => 'BL1', 'external_id' => '5'],
        ['name' => 'Borussia Dortmund', 'league' => 'BL1', 'external_id' => '4'],
        ['name' => 'AS Roma',           'league' => 'SA',  'external_id' => '100'],
        ['name' => 'Juventus',          'league' => 'SA',  'external_id' => '109'],
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
