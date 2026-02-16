<?php
declare(strict_types=1);

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class ExchangeRateControllerTest extends WebTestCase
{
    public function testGetRatesNoToken(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/EUR/2026-02-08/2026-02-10');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testGetRatesInvalidCurrency(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/XXX/2026-02-08/2026-02-10', [], [], [
            'HTTP_X-TOKEN-SYSTEM' => 'a1234567890abcdef',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetRatesRangeTooBig(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/EUR/2026-01-01/2026-02-15', [], [], [
            'HTTP_X-TOKEN-SYSTEM' => 'a1234567890abcdef',
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testGetRatesValid(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/rates/USD/2026-02-01/2026-02-07', [], [], [
            'HTTP_X-TOKEN-SYSTEM' => 'a1234567890abcdef',
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertJson($client->getResponse()->getContent());
    }
}
