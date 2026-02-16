<?php
declare(strict_types=1);

namespace App\Service;

use App\DTO\ExchangeRateDTO;

final class NBPXmlParser
{
    /** @return ExchangeRateDTO[] */
    public function parse(string $xml): array
    {
        $doc = @simplexml_load_string($xml);

        if ($doc === false) {
            throw new \RuntimeException('Failed to parse NBP XML response.');
        }

        $rates = [];

        foreach ($doc->Rates->Rate as $rate) {
            $rates[] = new ExchangeRateDTO(
                date: new \DateTimeImmutable((string) $rate->EffectiveDate),
                buyRate: (float) str_replace(',', '.', (string) $rate->Bid),
                sellRate: (float) str_replace(',', '.', (string) $rate->Ask),
            );
        }

        return $rates;
    }
}
