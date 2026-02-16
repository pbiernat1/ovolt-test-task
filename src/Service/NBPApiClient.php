<?php
declare(strict_types=1);

namespace App\Service;

use App\DTO\DateRangeDTO;
use App\DTO\ExchangeRateDTO;
use App\Enum\Currency;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class NBPApiClient
{
    private const string BASE_URL = 'https://api.nbp.pl/api/exchangerates/rates/c';

    public function __construct(
        private HttpClientInterface $client,
        private NBPXmlParser $parser,
        private RateDiffCalculator $diffCalculator,
    ) {}

    /** @return ExchangeRateDTO[] */
    public function getExchangeRates(Currency $currency, DateRangeDTO $range): array
    {
        $url = sprintf(
            '%s/%s/%s/%s',
            self::BASE_URL,
            $currency->value,
            $range->formatStart(),
            $range->formatEnd(),
        );

        $response = $this->client->request('GET', $url, [
            'query' => ['format' => 'xml'],
        ]);

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                sprintf('NBP API returned HTTP %d.', $response->getStatusCode()),
            );
        }

        $rates = $this->parser->parse($response->getContent());

        return $this->diffCalculator->calculate($rates);
    }
}
