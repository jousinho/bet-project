<?php

declare(strict_types=1);

namespace App\Domain\Betting\Service;

class SeasonResolver
{
    public function resolve(\DateTimeImmutable $date): string
    {
        $year  = (int) $date->format('Y');
        $month = (int) $date->format('n');

        if ($month >= 8) {
            return $year . '/' . substr((string) ($year + 1), 2);
        }

        return ($year - 1) . '/' . substr((string) $year, 2);
    }
}
