<?php
declare(strict_types=1);

namespace App\DTO;

class ExchangeRateDTO
{
    /**
     * @param string $date Date in format YYYY-MM-DD
     * @param float $buyRate Buy rate
     * @param float $sellRate Sell rate
     * @param float|null $buyDifference Difference in buy rate compared to previous day
     * @param float|null $sellDifference Difference in sell rate compared to previous day
     */
    public function __construct(
        public readonly string $date,
        public readonly float $buyRate,
        public readonly float $sellRate,
        public ?float $buyDifference = null,
        public ?float $sellDifference = null,
    ) {}

    /**
     * @return string
     */
    public function getDate(): string
    {
        return $this->date;
    }

    /**
     * @return float
     */
    public function getBuyRate(): float
    {
        return $this->buyRate;
    }

    /**
     * @return float
     */
    public function getSellRate(): float
    {
        return $this->sellRate;
    }

    /**
     * @return float|null
     */
    public function getBuyDifference(): ?float
    {
        return $this->buyDifference;
    }

    /**
     * @param float $buyDifference
     * @return void
     */
    public function setBuyDifference(float $buyDifference)
    {
        $this->buyDifference = $buyDifference;
    }

    /**
     * @return float|null
     */
    public function getSellDifference(): ?float
    {
        return $this->sellDifference;
    }

    /**
     * @param float $sellDifference
     * @return void
     */
    public function setSellDifference(float $sellDifference)
    {
        $this->sellDifference = $sellDifference;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'date' => $this->date,
            'buyRate' => $this->buyRate,
            'sellRate' => $this->sellRate,
            'buyDiff' => $this->buyDifference,
            'sellDiff' => $this->sellDifference,
        ];
    }
}
