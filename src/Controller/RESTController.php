<?php
declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class RESTController extends AbstractController
{
    /**
     * Shortcut for Response::JSON() method
     *
     * @param string $message
     * @param array $data
     * @param int $status
     * @param bool $success
     */
    public function response(
        string $message = '',
        array $data = [],
        int $status = Response::HTTP_OK,
        bool $success = true
    ) {
        if (!$success) {
            return $this->json([
                'success' => $success,
                'message' => $message,
            ], $status);
        }

        return $this->json([
            'success' => $success,
            'data' => $data,
        ], $status);

    }
}
