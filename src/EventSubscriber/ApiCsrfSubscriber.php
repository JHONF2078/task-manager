<?php declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class ApiCsrfSubscriber implements EventSubscriberInterface
{
    private array $exempt = [
        '/api/login',
        '/api/register',
        '/api/auth/token/refresh',
        '/api/auth/logout',
        '/api/auth/password/forgot',
        '/api/auth/password/reset',
        '/api/csrf',
    ];

    public function __construct(private CsrfTokenManagerInterface $csrf)
    {
    }

    public static function getSubscribedEvents() : array
    {
        return [KernelEvents::CONTROLLER => 'onController'];
    }

    public function onController(ControllerEvent $event) : void
    {
        $request = $event->getRequest();
        $path    = $request->getPathInfo();
        if (!str_starts_with($path, '/api/')) {
            return;
        }
        $method = $request->getMethod();
        if (!in_array($method, ['POST','PUT','PATCH','DELETE'], true)) {
            return;
        }
        if (in_array($path, $this->exempt, true)) {
            return;
        }

        $tokenVal = $request->headers->get('X-CSRF-Token') ?? $request->request->get('_csrf_token');
        if (!$tokenVal || !$this->csrf->isTokenValid(new CsrfToken('submit', $tokenVal))) {
            $event->setController(fn () => new JsonResponse(['error' => 'CSRF token inválido o ausente', 'code' => 419], 419));
        }
    }
}
