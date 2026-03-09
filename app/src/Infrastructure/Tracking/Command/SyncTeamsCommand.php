<?php

declare(strict_types=1);

namespace App\Infrastructure\Tracking\Command;

use App\Application\Tracking\Service\TeamSyncService;
use App\Domain\Tracking\Repository\TeamRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:teams:sync', description: 'Syncs all teams from the football data provider')]
class SyncTeamsCommand extends Command
{
    public function __construct(
        private readonly TeamRepositoryInterface $teamRepository,
        private readonly TeamSyncService $teamSyncService,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $teams = $this->teamRepository->findAll();

        $io->progressStart(count($teams));

        foreach ($teams as $team) {
            $this->teamSyncService->sync($team);
            $io->progressAdvance();
            sleep(7);
        }

        $io->progressFinish();
        $io->success(sprintf('Synced %d teams.', count($teams)));

        return Command::SUCCESS;
    }
}
