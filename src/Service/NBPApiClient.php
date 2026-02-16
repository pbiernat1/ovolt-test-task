<?php
declare(strict_types=1);

namespace App\Service;

use App\DTO\ExchangeRateDTO;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Serializer\SerializerInterface;

class NBPApiClient
{
    private const CURRENCY_CODES = [
        'EUR' => 'eur',
        'USD' => 'usd',
        'CHF' => 'chf',
    ];

    private const NBP_API_URL = 'https://api.nbp.pl/api/exchangerates/rates/c';

    public function __construct(
        private HttpClientInterface $client,
        private SerializerInterface $serializer
    ) {}

    /**
     * @param string $currency
     * @param \DateTimeInterface $startDate
     * @param \DateTimeInterface $endDate
     * @return ExchangeRateDTO[]
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     */
    public function getExchangeRates(
        string $currency,
        \DateTimeInterface $startDate,
        \DateTimeInterface $endDate
    ): array {
        $currencyCode = $this->mapCurrencyCode($currency);
        $startDateStr = $startDate->format('Y-m-d');
        $endDateStr = $endDate->format('Y-m-d');

        $url = sprintf(
            '%s/%s/%s/%s/?format=xml',
            self::NBP_API_URL,
            $currencyCode,
            $startDateStr,
            $endDateStr
        );

        try {
            $response = $this->client->request('GET', $url);
            $xmlContent = $response->getContent();

            return $this->parseXmlResponse($xmlContent);
        } catch (TransportExceptionInterface | ClientExceptionInterface | ServerExceptionInterface $e) {
            throw new \RuntimeException(
                sprintf('Unable to fetch data from NBP API: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * @param string $currency
     * @return string
     * @throws \InvalidArgumentException
     */
    private function mapCurrencyCode(string $currency): string
    {
        $currency = strtoupper($currency);

        if (!isset(self::CURRENCY_CODES[$currency])) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Unsupported currency: %s. Available: %s',
                    $currency,
                    implode(', ', array_keys(self::CURRENCY_CODES))
                )
            );
        }

        return self::CURRENCY_CODES[$currency];
    }

    /**
     * @param string $xmlContent
     * @return ExchangeRateDTO[]
     * @throws \RuntimeException
     */
    private function parseXmlResponse(string $xmlContent): array
    {
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($xmlContent);

        if ($xml === false) {
            $errors = libxml_get_errors();
            libxml_clear_errors();
            throw new \RuntimeException(
                sprintf('Error parsing XML: %s', $errors[0]->message ?? 'Unknown error')
            );
        }

        $rates = [];
        $previousBuyRate = null;
        $previousSellRate = null;

        foreach ($xml->Rates as $ratesTable) {
            foreach ($ratesTable as $rate) {
                $date = (string) $rate->EffectiveDate;
                $buyRate = (float) str_replace(',', '.', (string) $rate->Bid);
                $sellRate = (float) str_replace(',', '.', (string) $rate->Ask);

                $buyDifference = null;
                $sellDifference = null;

                if ($previousBuyRate !== null && $previousSellRate !== null) {
                    $buyDifference = round($buyRate - $previousBuyRate, 4);
                    $sellDifference = round($sellRate - $previousSellRate, 4);
                }

                $rates[] = new ExchangeRateDTO(
                    $date,
                    $buyRate,
                    $sellRate,
                    $buyDifference,
                    $sellDifference
                );

                $previousBuyRate = $buyRate;
                $previousSellRate = $sellRate;
            }
        }

        return $rates;
    }
}
