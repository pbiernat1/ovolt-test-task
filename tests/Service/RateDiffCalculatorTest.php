<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\DTO\ExchangeRateDTO;
use App\Service\RateDiffCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class RateDiffCalculatorTest extends TestCase
{
    private RateDiffCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new RateDiffCalculator();
    }

    #[Test]
    public function it_returns_empty_array_for_empty_input(): void
    {
        $this->assertSame([], $this->calculator->calculate([]));
    }

    #[Test]
    public function it_leaves_single_rate_without_diff(): void
    {
        $rates = [new ExchangeRateDTO(new \DateTimeImmutable('2026-02-11'), 4.3256, 4.4124)];

        $result = $this->calculator->calculate($rates);

        $this->assertCount(1, $result);
        $this->assertNull($result[0]->buyDiff);
        $this->assertNull($result[0]->sellDiff);
    }

    #[Test]
    public function it_calculates_positive_differences(): void
    {
        $rates = [
            new ExchangeRateDTO(new \DateTimeImmutable('2026-02-11'), 4.3256, 4.4124),
            new ExchangeRateDTO(new \DateTimeImmutable('2026-02-12'), 4.3379, 4.4251),
        ];

        $result = $this->calculator->calculate($rates);

        $this->assertNull($result[0]->buyDiff);
        $this->assertSame(0.0123, $result[1]->buyDiff);
        $this->assertSame(0.0127, $result[1]->sellDiff);
    }

    #[Test]
    public function it_calculates_negative_differences(): void
    {
        $rates = [
            new ExchangeRateDTO(new \DateTimeImmutable('2026-02-11'), 4.3379, 4.4251),
            new ExchangeRateDTO(new \DateTimeImmutable('2026-02-12'), 4.3302, 4.4172),
        ];

        $result = $this->calculator->calculate($rates);

        $this->assertSame(-0.0077, $result[1]->buyDiff);
        $this->assertSame(-0.0079, $result[1]->sellDiff);
    }

    #[Test]
    public function it_calculates_across_multiple_rates(): void
    {
        $rates = [
            new ExchangeRateDTO(new \DateTimeImmutable('2026-02-11'), 4.3256, 4.4124),
            new ExchangeRateDTO(new \DateTimeImmutable('2026-02-12'), 4.3379, 4.4251),
            new ExchangeRateDTO(new \DateTimeImmutable('2026-02-13'), 4.3302, 4.4172),
        ];

        $result = $this->calculator->calculate($rates);

        $this->assertCount(3, $result);
        $this->assertNull($result[0]->buyDiff);
        $this->assertSame(0.0123, $result[1]->buyDiff);
        $this->assertSame(-0.0077, $result[2]->buyDiff);
        $this->assertSame(-0.0079, $result[2]->sellDiff);
    }
}
