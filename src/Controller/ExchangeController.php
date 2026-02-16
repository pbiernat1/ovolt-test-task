<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\NBPApiClient;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use OpenApi\Attributes as OA;
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
    public function getRates(
        string $currency,
        string $start,
        string $end,
        Request $request
    ): JsonResponse {
        $data = $this->validator->validate([
            'currency' => $currency,
            'start' => $start,
            'end' => $end,
        ], null, ['api']);

        if (count($data) > 0) {
            return $this->response(success: false, message: 'Validation failed', status: Response::HTTP_BAD_REQUEST);
        }

        $startDate = new DateTimeImmutable($start);
        $endDate = new DateTimeImmutable($end);

        if ($endDate->diff($startDate)->days > 7) {
            return $this->response(success: false, message: 'Max 7 days range', status: Response::HTTP_BAD_REQUEST);
        }

        $allowed = ['EUR', 'USD', 'CHF'];
        if (!in_array($currency, $allowed, true)) {
            return $this->response(success: false, message: 'Invalid currency', status: Response::HTTP_BAD_REQUEST);
        }

        $rates = $this->nbpApiClient->getExchangeRates($currency, $startDate, $endDate);

        return $this->response(data: $rates);
    }
}
