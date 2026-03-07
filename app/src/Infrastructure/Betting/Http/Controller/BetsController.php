<?php

declare(strict_types=1);

namespace App\Infrastructure\Betting\Http\Controller;

use App\Application\Betting\Service\TomorrowBetsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BetsController extends AbstractController
{
    public function __construct(
        private readonly TomorrowBetsService $tomorrowBetsService,
    ) {}

    #[Route('/tomorrow/bets', name: 'tomorrow_bets')]
    public function index(): Response
    {
        $bets = $this->tomorrowBetsService->getData();

        return $this->render('bets/tomorrow.html.twig', ['bets' => $bets]);
    }
}
