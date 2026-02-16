<?php
declare(strict_types=1);

namespace App\Service;

use App\DTO\ExchangeRateDTO;

final class RateDiffCalculator
{
    /**
     * @param ExchangeRateDTO[] $rates
     * @return ExchangeRateDTO[]
     */
    public function calculate(array $rates): array
    {
        $result = [];
        $previous = null;

        foreach ($rates as $rate) {
            if ($previous !== null) {
                $rate = new ExchangeRateDTO(
                    date: $rate->date,
                    buyRate: $rate->buyRate,
                    sellRate: $rate->sellRate,
                    buyDiff: round($rate->buyRate - $previous->buyRate, 4),
                    sellDiff: round($rate->sellRate - $previous->sellRate, 4),
                );
            }

            $result[] = $rate;
            $previous = $rate;
        }

        return $result;
    }
}
