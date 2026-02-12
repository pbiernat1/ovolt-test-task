<?php
declare(strict_types=1);

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class NBPApiClient
{
    private const BASE_URL = 'https://api.nbp.pl/api/exchangerates/';

    private const TABLE_C = 'c';

    public function __construct(
        private HttpClientInterface $client
    ) {
    }

    public function getCurrentTableC(): array
    {
        $url = sprintf('%s/tables/%s/?format=xml', self::BASE_URL, self::TABLE_C);

        return $this->fetchAndParseXml($url);
    }

    public function getTableCByDate(string $date): array
    {
        $url = sprintf('%s/tables/%s/%s/?format=xml', self::BASE_URL, self::TABLE_C, $date);

        return $this->fetchAndParseXml($url);
    }

    public function getTableCByDateRange(string $startDate, string $endDate): array
    {
        $url = sprintf(
            '%s/tables/%s/%s/%s/?format=xml',
            self::BASE_URL,
            self::TABLE_C,
            $startDate,
            $endDate
        );

        return $this->fetchAndParseXml($url);
    }

    public function getCurrencyFromTableC(string $currencyCode): array
    {
        $url = sprintf(
            '%s/rates/%s/%s/?format=xml',
            self::BASE_URL,
            self::TABLE_C,
            strtolower($currencyCode)
        );

        return $this->fetchAndParseXml($url);
    }

    public function formatExchangeRates(array $data): array
    {
        $formatted = [];

        if (isset($data['Rate'])) {
            // Single currency response
            $rates = is_array($data['Rate']) && isset($data['Rate'][0])
                ? $data['Rate']
                : [$data['Rate']];
        } elseif (isset($data[0]['Rate'])) {
            // Table response
            $rates = is_array($data[0]['Rate'][0]) ? $data[0]['Rate'] : [$data[0]['Rate']];
        } else {
            return [];
        }

        foreach ($rates as $rate) {
            $formatted[] = [
                'currency' => $rate['Currency'] ?? null,
                'code' => $rate['Code'] ?? null,
                'bid' => $rate['Bid'] ?? null,  // Buy rate
                'ask' => $rate['Ask'] ?? null,  // Sell rate
            ];
        }

        return $formatted;
    }

    private function fetchAndParseXml(string $url): array
    {
        try {
            $response = $this->client->request('GET', $url, [
                'headers' => [
                    'Accept' => 'application/xml',
                ],
                'timeout' => 10,
            ]);

            $statusCode = $response->getStatusCode();

            if ($statusCode !== 200) {
                throw new \Exception("NBP API returned status code: {$statusCode}");
            }

            $xmlContent = $response->getContent();

            // Parse XML
            $xml = simplexml_load_string($xmlContent);

            if ($xml === false) {
                throw new \Exception('Failed to parse XML response');
            }

            // Convert SimpleXML to array
            return json_decode(json_encode($xml), true);

        } catch (\Exception $e) {
            throw new \Exception('Failed to connect to NBP API: ' . $e->getMessage());
        }
    }
}
