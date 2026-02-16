<?php
declare(strict_types=1);

namespace App\Controller;

use App\DTO\DateRangeDTO;
use App\Enum\Currency;
use App\Service\NBPApiClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use DateTimeImmutable;

#[Route('/api/rates', name: 'api_rates')]
final class ExchangeController extends RESTController
{
    public function __construct(
        private NBPApiClient $nbpApiClient,
        private ValidatorInterface $validator
    ) {}

    #[Route('/{currency}/{start}/{end}', name: '_get', methods: ['GET'])]
    #[OA\Tag(name: 'Exchange Rates')]
    #[OA\Parameter(name: 'currency', description: 'Currency code (EUR, USD, CHF)', in: 'path', required: true, schema: new OA\Schema(enum: ['EUR', 'USD', 'CHF']))]
    #[OA\Parameter(name: 'start', description: 'Start date YYYY-MM-DD', in: 'path', required: true)]
    #[OA\Parameter(name: 'end', description: 'End date YYYY-MM-DD', in: 'path', required: true)]
    #[OA\Response(response: 200, description: 'Exchange rates')]
    public function getRates(string $currency, string $start, string $end): JsonResponse
    {
        try {
            $currencyEnum = Currency::fromInput($currency);
        } catch (\ValueError) {
            return $this->response(
                success: false,
                message: sprintf('Unsupported currency: %s. Allowed: %s.', $currency, implode(', ', array_column(Currency::cases(), 'name'))),
                status: Response::HTTP_BAD_REQUEST,
            );
        }

        try {
            $range = new DateRangeDTO($start, $end);
        } catch (\InvalidArgumentException $e) {
            return $this->response(
                success: false,
                message: $e->getMessage(),
                status: Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $rates = $this->nbpApiClient->getExchangeRates($currencyEnum, $range);
        } catch (\RuntimeException $e) {
            return $this->response(
                success: false,
                message: $e->getMessage(),
                status: Response::HTTP_BAD_GATEWAY
            );
        }

        return $this->json(['data' => $rates], context: [
            DateTimeNormalizer::FORMAT_KEY => 'Y-m-d',
        ]);
    }
}
