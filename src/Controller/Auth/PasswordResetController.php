<?php declare(strict_types=1);

namespace App\Controller\Auth;

use App\Exception\ValidationException;
use App\Service\AuthService;
use App\Service\PasswordResetService;
use App\Service\ResetPasswordMailService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/auth/password')]
class PasswordResetController extends AbstractController
{
    public function __construct(
        private PasswordResetService $passwordResetService,
        private AuthService $authService,
        private ResetPasswordMailService $resetPasswordMailService,
    ) {
    }

    // Endpoint principal (nuevo)
    #[Route('/forgot', name: 'api_auth_password_forgot', methods: ['POST'])]
    //#[Route('/api/password/forgot', name: 'api_password_forgot_compat', methods: ['POST'])]
    public function forgot(Request $request) : JsonResponse
    {
        $data    = json_decode($request->getContent(), true) ?? [];
        $email   = trim($data['email'] ?? '');
        $generic = ['message' => 'Si el email existe se ha enviado un enlace de recuperación'];
        if ($email === '') {
            return $this->json($generic);
        }
        $user = $this->authService->getUserByEmail($email);
        if ($user) {
            $ttl      = (int)($_ENV['RESET_TOKEN_TTL_MINUTES'] ?? 60);
            $token    = $this->passwordResetService->generateResetToken($user, $ttl);
            $base     = rtrim($_ENV['FRONTEND_BASE_URL'] ?? $request->getSchemeAndHttpHost(), '/');
            $resetUrl = $base . '/reset-password/' . $token;
            $this->resetPasswordMailService->send($user, $token, $resetUrl);
        }
        $response = $this->json($generic + (($_ENV['APP_ENV'] ?? 'prod') === 'dev' && isset($token) ? ['dev_reset_token' => $token, 'dev_reset_url' => $resetUrl] : []));
        if ($request->attributes->get('_route') === 'api_password_forgot_compat') {
            $response->headers->set('Warning', '299 - "Deprecated: usa /api/auth/password/forgot; esta ruta se eliminará"');
        }
        return $response;
    }

    #[Route('/reset', name: 'api_auth_password_reset', methods: ['POST'])]
    //#[Route('/api/password/reset', name: 'api_password_reset_compat', methods: ['POST'])]
    public function reset(Request $request) : JsonResponse
    {
        $data        = json_decode($request->getContent(), true) ?? [];
        $token       = $data['token']                            ?? '';
        $newPassword = $data['new_password']                     ?? '';
        if ($token === '' || $newPassword === '') {
            throw new ValidationException([], 'Datos incompletos');
        }
        $user = $this->passwordResetService->resetPassword($token, $newPassword);
        if (!$user) {
            throw new ValidationException([], 'Token inválido o expirado');
        }
        $response = $this->json(['message' => 'Contraseña actualizada correctamente']);
        if ($request->attributes->get('_route') === 'api_password_reset_compat') {
            $response->headers->set('Warning', '299 - "Deprecated: usa /api/auth/password/reset; esta ruta se eliminará"');
        }
        return $response;
    }
}
