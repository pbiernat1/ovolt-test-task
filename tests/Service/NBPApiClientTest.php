<?php

declare(strict_types=1);

namespace App\Tests\Service;


use App\Service\NBPApiClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class NBPApiClientTest extends TestCase
{
    private NBPApiClient $nbpApiClient;
    private MockHttpClient $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $serializer = new Serializer([new ObjectNormalizer()], [new XmlEncoder()]);
        $this->nbpApiClient = new NBPApiClient($this->httpClient, $serializer);
    }

    /**
     * @return void
     */
    public function testGetExchangeRatesWithUnsupportedCurrencyThrowsException(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported currency: ZZZ');

        $startDate = new \DateTimeImmutable('2026-02-11');
        $endDate = new \DateTimeImmutable('2026-02-12');

        $this->nbpApiClient->getExchangeRates('ZZZ', $startDate, $endDate);
    }

    /**
     * @return void
     */
    public function testParseXmlResponseCalculatesDifferences(): void
    {
        $xmlContent = '<?xml version="1.0" encoding="UTF-8"?>
        <ExchangeRatesSeries>
            <Table>C</Table>
            <Currency>euro</Currency>
            <Code>EUR</Code>
            <Rates>
                <Rate>
                    <No>001/C/NBP/2026</No>
                    <EffectiveDate>2026-02-11</EffectiveDate>
                    <Bid>4.3256</Bid>
                    <Ask>4.4124</Ask>
                </Rate>
                <Rate>
                    <No>002/C/NBP/2026</No>
                    <EffectiveDate>2026-02-12</EffectiveDate>
                    <Bid>4.3379</Bid>
                    <Ask>4.4251</Ask>
                </Rate>
                <Rate>
                    <No>003/C/NBP/2026</No>
                    <EffectiveDate>2026-02-13</EffectiveDate>
                    <Bid>4.3302</Bid>
                    <Ask>4.4172</Ask>
                </Rate>
            </Rates>
        </ExchangeRatesSeries>';

        $reflection = new \ReflectionClass($this->nbpApiClient);
        $method = $reflection->getMethod('parseXmlResponse');
        $method->setAccessible(true);

        $result = $method->invoke($this->nbpApiClient, $xmlContent);

        $this->assertCount(3, $result);

        // First exchange rate - no diff
        $this->assertEquals('2026-02-11', $result[0]->getDate());
        $this->assertEquals(4.3256, $result[0]->getBuyRate());
        $this->assertEquals(4.4124, $result[0]->getSellRate());
        $this->assertNull($result[0]->getBuyDifference());
        $this->assertNull($result[0]->getSellDifference());

        // Second exchnage rate - with diffs
        $this->assertEquals('2026-02-12', $result[1]->getDate());
        $this->assertEquals(4.3379, $result[1]->getBuyRate());
        $this->assertEquals(4.4251, $result[1]->getSellRate());
        $this->assertEquals(0.0123, $result[1]->getBuyDifference());
        $this->assertEquals(0.0127, $result[1]->getSellDifference());

        // Third exchange rate - with diffs
        $this->assertEquals('2026-02-13', $result[2]->getDate());
        $this->assertEquals(4.3302, $result[2]->getBuyRate());
        $this->assertEquals(4.4172, $result[2]->getSellRate());
        $this->assertEquals(-0.0077, $result[2]->getBuyDifference());
        $this->assertEquals(-0.0079, $result[2]->getSellDifference());
    }

    /**
     * @return void
     */
    public function testParseXmlResponseThrowsExceptionOnInvalidXml(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error parsing XML');

        $invalidXml = 'This is not valid XML';

        $reflection = new \ReflectionClass($this->nbpApiClient);
        $method = $reflection->getMethod('parseXmlResponse');
        $method->setAccessible(true);

        $method->invoke($this->nbpApiClient, $invalidXml);
    }
}
