<?php

declare(strict_types=1);

namespace App\Infrastructure\Betting\Http\Controller;

use App\Domain\Betting\Repository\BetRepositoryInterface;
use App\Domain\Betting\Repository\TeamBetStatsRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BetsHistoryController extends AbstractController
{
    public function __construct(
        private readonly BetRepositoryInterface $betRepository,
        private readonly TeamBetStatsRepositoryInterface $statsRepository,
    ) {}

    #[Route('/bets/history', name: 'bets_history')]
    public function index(): Response
    {
        return $this->render('bets/history.html.twig', [
            'bets'  => $this->betRepository->findAll(),
            'stats' => $this->statsRepository->findAll(),
        ]);
    }
}
