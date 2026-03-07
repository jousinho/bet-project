<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Service;

use App\Domain\Betting\Service\FormCalculator;
use PHPUnit\Framework\TestCase;

class FormCalculatorTest extends TestCase
{
    private FormCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new FormCalculator();
    }

    private function makeMatch(string $result, bool $isHome = true): array
    {
        return ['result' => $result, 'isHome' => $isHome, 'goalsScored' => 1, 'goalsAgainst' => 0, 'date' => '2025-01-01'];
    }

    public function test_calculating_form__with_5_wins__should_return_WWWWW(): void
    {
        $matches = array_fill(0, 5, $this->makeMatch('W'));

        $this->assertSame('WWWWW', $this->calculator->calculate($matches, 5));
    }

    public function test_calculating_form__with_mixed_results__should_return_correct_string(): void
    {
        $matches = [
            $this->makeMatch('W'),
            $this->makeMatch('D'),
            $this->makeMatch('L'),
            $this->makeMatch('W'),
            $this->makeMatch('W'),
        ];

        $this->assertSame('WDLWW', $this->calculator->calculate($matches, 5));
    }

    public function test_calculating_form__when_fewer_than_requested_matches__should_return_available_results(): void
    {
        $matches = [$this->makeMatch('W'), $this->makeMatch('L')];

        $this->assertSame('WL', $this->calculator->calculate($matches, 5));
    }

    public function test_calculating_form__when_no_matches__should_return_empty_string(): void
    {
        $this->assertSame('', $this->calculator->calculate([], 5));
    }
}
