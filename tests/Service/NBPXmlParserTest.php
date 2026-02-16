<?php
declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\NbpXmlParser;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class NBPXmlParserTest extends TestCase
{
    private NbpXmlParser $parser;

    protected function setUp(): void
    {
        $this->parser = new NbpXmlParser();
    }

    #[Test]
    public function it_parses_rates_from_xml(): void
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
                <Rate>
                    <EffectiveDate>2026-02-12</EffectiveDate>
                    <Bid>4,3379</Bid>
                    <Ask>4,4251</Ask>
                </Rate>
            </Rates>
        </ExchangeRatesSeries>
        XML;

        $rates = $this->parser->parse($xml);

        $this->assertCount(2, $rates);

        $this->assertSame('2026-02-11', $rates[0]->date->format('Y-m-d'));
        $this->assertSame(4.3256, $rates[0]->buyRate);
        $this->assertSame(4.4124, $rates[0]->sellRate);
        $this->assertNull($rates[0]->buyDiff);
        $this->assertNull($rates[0]->sellDiff);

        $this->assertSame('2026-02-12', $rates[1]->date->format('Y-m-d'));
        $this->assertSame(4.3379, $rates[1]->buyRate);
        $this->assertSame(4.4251, $rates[1]->sellRate);
    }

    #[Test]
    public function it_handles_comma_decimal_separator(): void
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

        $rates = $this->parser->parse($xml);

        $this->assertSame(4.3256, $rates[0]->buyRate);
    }

    #[Test]
    public function it_returns_empty_array_for_no_rates(): void
    {
        $xml = <<<'XML'
        <?xml version="1.0" encoding="utf-8"?>
        <ExchangeRatesSeries>
            <Rates/>
        </ExchangeRatesSeries>
        XML;

        $this->assertSame([], $this->parser->parse($xml));
    }

    #[Test]
    public function it_throws_on_invalid_xml(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Failed to parse');

        $this->parser->parse('not xml');
    }
}
