<?php declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * EventSubscriber para protección CSRF en APIs usando el patrón double-submit stateless.
 *
 * Estrategia:
 *  - El backend expone /api/csrf y devuelve el nombre base de la cookie (ej: 'csrf-token').
 *  - El frontend genera un token aleatorio, crea la cookie csrf-token_<token>=<token> y envía ese token en el header X-CSRF-Token.
 *  - El backend valida que el valor del header y la cookie coincidan.
 *  - No depende de sesión ni de tokens generados por el backend.
 */
class ApiCsrfSubscriber implements EventSubscriberInterface
{
    /**
     * Rutas exentas de validación CSRF.
     */
    private array $exempt = [
        '/api/login',
        '/api/register',
        '/api/auth/token/refresh',
        '/api/auth/logout',
        '/api/auth/password/forgot',
        '/api/auth/password/reset',
        '/api/csrf',
    ];

    public function __construct(private CsrfTokenManagerInterface $csrf) {}

    public static function getSubscribedEvents() : array
    {
        return [KernelEvents::CONTROLLER => 'onController'];
    }

    /**
     * Valida el patrón double-submit CSRF en peticiones mutantes de la API.
     */
    public function onController(ControllerEvent $event) : void
    {
        $request = $event->getRequest();
        $path    = $request->getPathInfo();
        if (!str_starts_with($path, '/api/')) return;
        $method = $request->getMethod();
        if (!in_array($method, ['POST','PUT','PATCH','DELETE'], true)) return;
        if (in_array($path, $this->exempt, true)) return;

        // Obtiene el token enviado en el header
        $tokenVal = $request->headers->get('X-CSRF-Token');
        // Busca la cookie con el nombre base y el token como sufijo
        $cookieName = 'csrf-token_' . $tokenVal;
        $cookieVal = $request->cookies->get($cookieName);

        // Valida que ambos existan y sean iguales
        if (!$tokenVal || !$cookieVal || $tokenVal !== $cookieVal) {
            $event->setController(fn () => new JsonResponse([
                'error' => 'CSRF token inválido o ausente',
                'code' => 419
            ], 419));
        }
    }
}
