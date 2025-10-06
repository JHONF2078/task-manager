<?php declare(strict_types=1);

namespace App\EventSubscriber;

use App\Exception\ConflictException;
use App\Exception\EntityNotFoundException;
use App\Exception\InvalidCredentialsException;
use App\Exception\RefreshTokenInvalidException;
use App\Exception\ValidationException;
use DateTimeInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

/**
 * Maneja las excepciones de la API y devuelve una respuesta JSON
 * con el formato Problem Details (RFC 9457).
 */
readonly class ProblemDetailsApiExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger,
        #[Autowire('%kernel.debug%')]
        private bool            $kernelDebug
    ) {
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
        if (!str_starts_with($request->getPathInfo(), '/api')) {
            return;
        }

        $exception = $event->getThrowable();
        $response  = $this->createErrorResponse($exception);
        $event->setResponse($response);
    }

    private function createErrorResponse(Throwable $e) : JsonResponse
    {
        $statusCode = $this->getStatusCode($e);

        $problemDetails = [
            'type'      => 'about:blank',
            'title'     => (new ReflectionClass($e))->getShortName(),
            'status'    => $statusCode,
            'detail'    => $e->getMessage(),
            'instance'  => $e->getRequest()->getUri(),
            'timestamp' => (new \DateTime())->format(DateTimeInterface::ATOM)
        ];

        if ($e instanceof ValidationException) {
            $problemDetails['violations'] = $e->getViolations();
        }

        if ($this->kernelDebug) {
            $problemDetails['debug'] = [
                'class' => get_class($e),
                'file'  => $e->getFile(),
                'line'  => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ];
        }

        if ($statusCode >= 500) {
            $this->logger->error($e->getMessage(), ['exception' => $e]);
        }

        return new JsonResponse($problemDetails, $statusCode, [
            'Content-Type' => 'application/problem+json'
        ]);
    }

    private function getStatusCode(Throwable $e) : int
    {
        if ($e instanceof HttpExceptionInterface) {
            return $e->getStatusCode();
        }

        return match (get_class($e)) {
            ValidationException::class => 400, //personalizada  Para errores de formato en DTOs.
            InvalidCredentialsException::class, //personalizada Para email/contraseña incorrectos.
            AuthenticationException::class, //La lanza el sistema de seguridad de Symfony si falla la autenticación (ej. token JWT malformado).
            RefreshTokenInvalidException::class => 401, //personalizada Para tokens de refresco inválidos o expirados.
            AccessDeniedException::class        => 403, //La lanza Symfony si un usuario está autenticado pero no tiene los roles necesarios para acceder a un recurso.
            EntityNotFoundException::class      => 404, //personalizada Para entidades no encontradas en la base de datos.
            ConflictException::class            => 409, //personalizada Para conflictos de datos, como emails duplicados.
            default                             => 500,
        };
    }
}
