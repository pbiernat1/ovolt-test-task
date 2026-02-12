<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Service\NBPAPIClient;

class ExchangeController extends AbstractController
{
    public function __construct(
        private NBPAPIClient $NBPApiClient
    ) {}

    #[Route('/api/exchange-rates/current', name: 'exchange_rates_current', methods: ['GET'])]
    public function getCurrentRates(): JsonResponse
    {
        try {
            $data = $this->NBPApiClient->getCurrentTableC();
            $formatted = $this->NBPApiClient->formatExchangeRates($data);

            return $this->json([
                'success' => true,
                'data' => $formatted,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    #[Route('/api/exchange-rates/currency/{code}', name: 'exchange_rates_currency', methods: ['GET'])]
    public function getCurrencyRate(string $code): JsonResponse
    {
        try {
            $data = $this->NBPApiClient->getCurrencyFromTableC($code);
            $formatted = $this->NBPApiClient->formatExchangeRates($data);

            return $this->json([
                'success' => true,
                'data' => $formatted,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    #[Route('/api/exchange-rates/date/{date}', name: 'exchange_rates_by_date', methods: ['GET'])]
    public function getRatesByDate(string $date): JsonResponse
    {
        try {
            $data = $this->NBPApiClient->getTableCByDate($date);
            $formatted = $this->NBPApiClient->formatExchangeRates($data);

            return $this->json([
                'success' => true,
                'data' => $formatted,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 404);
        }
    }
}
