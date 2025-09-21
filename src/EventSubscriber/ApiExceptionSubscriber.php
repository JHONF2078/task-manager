<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\ConflictException;
use App\Exception\EntityNotFoundException;
use App\Exception\InvalidCredentialsException;
use App\Exception\RefreshTokenInvalidException;
use App\Exception\ValidationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(private LoggerInterface $logger, #[Autowire('%kernel.debug%')] private bool $kernelDebug)
    {
    }

    public static function getSubscribedEvents() : array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event) : void
    {
        $request = $event->getRequest();
        // Solo formateamos JSON para endpoints API
        if (0 !== strpos($request->getPathInfo(), '/api')) {
            return;
        }

        $e       = $event->getThrowable();
        $status  = 500;
        $payload = [
            'error' => 'Error interno',
            'code'  => 500,
        ];

        if ($e instanceof InvalidCredentialsException) {
            $status  = 401;
            $payload = [
                'error' => $e->getMessage(),
                'code'  => 401,
            ];
        } elseif ($e instanceof AuthenticationException) {
            $status  = 401;
            $payload = [
                'error' => 'No autenticado',
                'code'  => 401,
            ];
        } elseif ($e instanceof AccessDeniedException || $e instanceof AccessDeniedHttpException) {
            $status  = 403;
            $payload = [
                'error' => 'Acceso denegado',
                'code'  => 403,
            ];
        } elseif ($e instanceof EntityNotFoundException) {
            $status  = 404;
            $payload = [
                'error' => $e->getMessage(),
                'code'  => 404,
            ];
        } elseif ($e instanceof ValidationException) {
            $status  = 400;
            $payload = [
                'error'  => $e->getMessage(),
                'code'   => 400,
                'errors' => method_exists($e, 'getErrors') ? $e->getErrors() : [],
            ];
        } elseif ($e instanceof ConflictException) {
            $status  = 409;
            $payload = [
                'error' => $e->getMessage(),
                'code'  => 409,
            ];
        } elseif ($e instanceof RefreshTokenInvalidException) {
            $status  = 401;
            $payload = [
                'error'  => $e->getMessage(),
                'code'   => 401,
                'reason' => 'refresh_token_invalid'
            ];
        } elseif ($e instanceof HttpExceptionInterface) {
            // Cualquier otra HttpException específica conserva su status
            $status  = $e->getStatusCode();
            $payload = [
                'error' => $e->getMessage() ?: 'Error HTTP',
                'code'  => $status,
            ];
        }

        // Logging siempre que sea 500
        if ($status >= 500) {
            $this->logger->error('API exception', [
                'path'      => $request->getPathInfo(),
                'exception' => $e,
            ]);
        }

        // Forzar información de debug automática en modo kernel.debug
        if ($this->kernelDebug || $request->headers->get('X-Debug-Api') === '1') {
            $payload['debug_message']   = $e->getMessage();
            $payload['exception_class'] = get_class($e);
            $payload['trace_snippet']   = array_slice(explode("\n", $e->getTraceAsString()), 0, 5);
        }

        $event->setResponse(new JsonResponse($payload, $status));
    }
}
