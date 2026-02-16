<?php
declare(strict_types=1);

namespace App\DTO;

final readonly class ExchangeRateDTO
{
    public function __construct(
        public \DateTimeImmutable $date,
        public float $buyRate,
        public float $sellRate,
        public ?float $buyDiff = null,
        public ?float $sellDiff = null,
    ) {}
}
