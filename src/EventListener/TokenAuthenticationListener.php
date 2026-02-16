<?php

declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class TokenAuthenticationListener
{
    private const TOKEN_HEADER = 'X-TOKEN-SYSTEM';

    /**
     * @param string $token
     */
    public function __construct(
        private readonly string $token
    ) {
    }

    /**
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (str_starts_with($request->getPathInfo(), '/api/doc')) {
            return;
        }

        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $token = $request->headers->get(self::TOKEN_HEADER);

        if ($token === null) {
            $response = new JsonResponse(
                [
                    'success' => false,
                    'message' => 'Missing token header',
                    'details' => sprintf('Required header: %s', self::TOKEN_HEADER)
                ],
                Response::HTTP_UNAUTHORIZED
            );
            $event->setResponse($response);

            return;
        }

        if ($token !== $this->token) {
            $response = new JsonResponse(
                [
                    'error' => 'Invalid auth token',
                ],
                Response::HTTP_UNAUTHORIZED
            );
            $event->setResponse($response);
        }
    }
}
