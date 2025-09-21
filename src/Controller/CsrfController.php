<?php declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

#[Route('/api')]
class CsrfController
{
    public function __construct(private CsrfTokenManagerInterface $csrf)
    {
    }

    #[Route('/csrf', name: 'api_csrf', methods: ['GET'])]
    public function token() : JsonResponse
    {
        $value = $this->csrf->getToken('submit')->getValue();
        return new JsonResponse(['token' => $value]);
    }
}
