<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Betting\Service;

use App\Domain\Betting\Service\SeasonResolver;
use PHPUnit\Framework\TestCase;

class SeasonResolverTest extends TestCase
{
    private SeasonResolver $resolver;

    protected function setUp(): void
    {
        $this->resolver = new SeasonResolver();
    }

    public function test_resolve__august__should_return_current_season(): void
    {
        $this->assertSame('2025/26', $this->resolver->resolve(new \DateTimeImmutable('2025-08-01')));
    }

    public function test_resolve__december__should_return_current_season(): void
    {
        $this->assertSame('2025/26', $this->resolver->resolve(new \DateTimeImmutable('2025-12-15')));
    }

    public function test_resolve__march__should_return_previous_start_season(): void
    {
        $this->assertSame('2025/26', $this->resolver->resolve(new \DateTimeImmutable('2026-03-08')));
    }

    public function test_resolve__july__should_return_previous_start_season(): void
    {
        $this->assertSame('2025/26', $this->resolver->resolve(new \DateTimeImmutable('2026-07-31')));
    }
}
