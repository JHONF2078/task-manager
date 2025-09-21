<?php
namespace App\EventSubscriber;

use App\Exception\ValidationException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\JsonResponse;

class ValidationExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {   return [ KernelEvents::EXCEPTION => 'onException' ]; }

    public function onException(ExceptionEvent $event): void
    {   $ex = $event->getThrowable();
        if ($ex instanceof ValidationException) {
            $resp = new JsonResponse([
                'error' => $ex->getMessage(),
                'violations' => $ex->getViolations(),
            ], 400);
            $event->setResponse($resp);
        }
    }
}

