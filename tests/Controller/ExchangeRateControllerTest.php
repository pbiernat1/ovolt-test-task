<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use App\DTO\DateRangeDTO;
use App\DTO\ExchangeRateDTO;
use App\Enum\Currency;
use App\Service\NBPApiClient;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\Test;

class ExchangeRateControllerTest extends WebTestCase
{
    private const string TOKEN = 'a1234567890abcdef';

    #[Test]
    public function it_returns_401_without_token(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/EUR/2026-02-08/2026-02-10');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }


    #[Test]
    public function it_returns_401_with_invalid_token(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/EUR/2026-02-10/2026-02-12', server: [
            'HTTP_X_TOKEN_SYSTEM' => 'wrong-token',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    #[Test]
    public function it_returns_400_for_unsupported_currency(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/XXX/2026-02-08/2026-02-10', [], [], [
            'HTTP_X-TOKEN-SYSTEM' => self::TOKEN,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetRatesRangeTooBig(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/EUR/2026-01-01/2026-02-15', [], [], [
            'HTTP_X-TOKEN-SYSTEM' => self::TOKEN,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[Test]
    public function it_returns_400_for_invalid_dates(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/EUR/garbage/2026-02-12', server: [
            'HTTP_X-TOKEN-SYSTEM' => self::TOKEN,
        ]);

        $this->assertResponseStatusCodeSame(expectedCode: Response::HTTP_BAD_REQUEST);
    }

    #[Test]
    public function it_returns_400_for_reversed_dates(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/EUR/2026-02-15/2026-02-10', server: [
            'HTTP_X_TOKEN_SYSTEM' => self::TOKEN,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[Test]
    public function it_returns_400_for_range_exceeding_7_days(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/EUR/2026-02-01/2026-02-15', server: [
            'HTTP_X_TOKEN_SYSTEM' => self::TOKEN,
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    #[Test]
    public function it_returns_200_with_exchange_rates(): void
    {
        $client = static::createClient();

        $mockNbpClient = $this->createMock(NBPApiClient::class);
        $mockNbpClient
            ->expects($this->once())
            ->method('getExchangeRates')
            ->with(Currency::EUR, $this->isInstanceOf(DateRangeDTO::class))
            ->willReturn([
                new ExchangeRateDTO(new \DateTimeImmutable('2026-02-10'), 4.3256, 4.4124),
                new ExchangeRateDTO(new \DateTimeImmutable('2026-02-11'), 4.3379, 4.4251, 0.0123, 0.0127),
            ]);

        static::getContainer()->set(NBPApiClient::class, $mockNbpClient);

        $client->request('GET', '/api/rates/EUR/2026-02-10/2026-02-12', server: [
            'HTTP_X_TOKEN_SYSTEM' => self::TOKEN,
        ]);

        $this->assertResponseStatusCodeSame(200);

        $json = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('data', $json);
        $this->assertCount(2, $json['data']);
        $this->assertSame('2026-02-10', $json['data'][0]['date']);
        $this->assertSame(4.3256, $json['data'][0]['buyRate']);
        $this->assertNull($json['data'][0]['buyDiff']);
        $this->assertSame(0.0123, $json['data'][1]['buyDiff']);
    }
}
