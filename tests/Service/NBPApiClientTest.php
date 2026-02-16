<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\DTO\DateRangeDTO;
use App\DTO\ExchangeRateDTO;
use App\Enum\Currency;
use App\Service\NBPApiClient;
use App\Service\NBPXmlParser;
use App\Service\RateDiffCalculator;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class NBPApiClientTest extends TestCase
{
    #[Test]
    public function it_fetches_and_returns_parsed_rates(): void
    {
        $xml = <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <ExchangeRatesSeries>
            <Rates>
                <Rate>
                    <EffectiveDate>2026-02-11</EffectiveDate>
                    <Bid>4,3256</Bid>
                    <Ask>4,4124</Ask>
                </Rate>
            </Rates>
        </ExchangeRatesSeries>
        XML;

        $httpClient = new MockHttpClient([new MockResponse($xml)]);
        $client = new NBPApiClient($httpClient, new NBPXmlParser(), new RateDiffCalculator());

        $rates = $client->getExchangeRates(Currency::EUR, new DateRangeDTO('2026-02-10', '2026-02-12'));

        $this->assertCount(1, $rates);
        $this->assertInstanceOf(ExchangeRateDTO::class, $rates[0]);
        $this->assertSame('2026-02-11', $rates[0]->date->format('Y-m-d'));
    }

    #[Test]
    public function it_throws_on_non_200_response(): void
    {
        $httpClient = new MockHttpClient([new MockResponse('', ['http_code' => 404])]);
        $client = new NBPApiClient($httpClient, new NBPXmlParser(), new RateDiffCalculator());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('NBP API returned HTTP 404');

        $client->getExchangeRates(Currency::EUR, new DateRangeDTO('2026-02-10', '2026-02-12'));
    }
}
