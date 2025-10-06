<?php declare(strict_types=1);

namespace App\Controller\Traits;

use DateTimeInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Throwable;

/**
 * Trait ApiErrorHandlerTrait
 * Proporciona un método para crear respuestas de error JSON estandarizadas para los controladores de API.
 * Sigue el estándar RFC 7807 para respuestas de error
 */
trait ApiErrorHandlerTrait
{
    protected function createErrorResponse(Throwable $e, array $details = []): JsonResponse
    {
        $statusCode = method_exists($e, 'getStatusCode')
            ? $e->getStatusCode()
            : JsonResponse::HTTP_INTERNAL_SERVER_ERROR;

        $errorData = [
            'status'    => 'error',
            'message'   => $e->getMessage(),
            'code'      => $statusCode,
            'type'      => (new ReflectionClass($e))->getShortName(),
            'timestamp' => (new \DateTime())->format(DateTimeInterface::ATOM)
        ];

        if ($details) {
            $errorData['details'] = $details;
        }

        if ($this->getParameter('kernel.debug')) {
            $errorData['debug'] = [
                'class'   => get_class($e),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString()
            ];
        }

        if ($e instanceof \App\Exception\ValidationException) {
            $errorData['violations'] = $e->getViolations();
        }

        return $this->json($errorData, $statusCode);
    }
}
