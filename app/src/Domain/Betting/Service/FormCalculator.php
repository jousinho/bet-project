<?php

declare(strict_types=1);

namespace App\Domain\Betting\Service;

class FormCalculator
{
    /**
     * Concatenates match results (W/D/L) up to the given limit.
     * Matches must be ordered most-recent-first.
     *
     * @param array<int, array{result: string, isHome: bool}> $matches
     */
    public function calculate(array $matches, int $limit): string
    {
        $form = '';
        $count = 0;

        foreach ($matches as $match) {
            if ($count >= $limit) {
                break;
            }
            $form .= $match['result'];
            $count++;
        }

        return $form;
    }
}
