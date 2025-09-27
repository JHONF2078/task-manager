<?php declare(strict_types=1);

namespace App\Controller;

use App\Dto\UserRegistrationInput;
use App\Entity\User;
use App\Exception\ConflictException;
use App\Exception\InvalidCredentialsException;
use App\Exception\RefreshTokenInvalidException;
use App\Exception\ValidationException;
use App\Service\AuthService;
use App\Service\RefreshTokenService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')] // Base prefix for all auth endpoints
class AuthController extends AbstractController
{
    public function __construct(
        private AuthService $authService,
        private JWTTokenManagerInterface $jwtManager,
        private TokenStorageInterface $tokenStorage,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private RefreshTokenService $refreshTokenService,
        private ValidatorInterface $validator
    ) {
    }

    private function createRefreshCookie(string $token, \DateTimeImmutable $expiresAt) : Cookie
    {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        return Cookie::create('refresh_token', $token)
            ->withHttpOnly(true)
            ->withSecure($secure)
            ->withSameSite('Strict')
            ->withPath('/')
            ->withExpires($expiresAt);
    }

    private function clearRefreshCookie() : Cookie
    {
        $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        return Cookie::create('refresh_token', '')
            ->withHttpOnly(true)
            ->withSecure($secure)
            ->withSameSite('Strict')
            ->withPath('/')
            ->withExpires((new \DateTimeImmutable('-1 hour')));
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request) : JsonResponse
    {
        $data          = json_decode($request->getContent(), true) ?? [];
        $dto           = new UserRegistrationInput();
        $dto->email    = (string)($data['email'] ?? '');
        $dto->password = (string)($data['password'] ?? '');
        $dto->name     = isset($data['name']) ? (string)$data['name'] : null;

        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = ['field' => $v->getPropertyPath(),'message' => $v->getMessage()];
            }
            throw new ValidationException($errors);
        }

        // Roles opcionales sólo si quien registra es admin
        $roles       = ['ROLE_USER'];
        // Bloque para asignar roles personalizados solo si quien registra es admin
        // Comentado porque en el autoregistro nunca se cumple esta condición
        /*
        $currentUser = $this->tokenStorage->getToken()?->getUser();
        if ($currentUser instanceof User && $this->isGranted('ROLE_ADMIN') && isset($data['roles']) && is_array($data['roles'])) {
            $roles = $data['roles'];
        }
        */

        try {
            $user = $this->authService->register($dto->email, $dto->password, $roles, $dto->name ?? '');
            if (!$user) {
                throw new ConflictException('El email ya está registrado');
            }
        } catch (ConflictException $e) {
            return $this->json(['error' => $e->getMessage()], 409);
        }

        return $this->json([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'name'  => $user->getName(),
            'roles' => $user->getRoles(),
        ], 201);
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request) : JsonResponse
    {
        $data     = json_decode($request->getContent(), true) ?? [];
        $email    = $data['email']                            ?? '';
        $password = $data['password']                         ?? '';

        try {
            $user = $this->authService->authenticate($email, $password);
        } catch (InvalidCredentialsException $e) {
            return $this->json(['error' => $e->getMessage(), 'code' => $e->getStatusCode()], $e->getStatusCode());
        }

        if (!$user->isActive()) {
            return $this->json(['error' => 'Usuario inactivo', 'code' => 403], 403);
        }

        try {
            $accessTtl        = (int)$this->params->get('lexik_jwt_authentication.token_ttl');
            $issuedAt         = time();
            $expiresAt        = $issuedAt + $accessTtl;
            $accessToken      = $this->jwtManager->create($user);
            $rtData           = $this->refreshTokenService->create($user);
            $refreshToken     = $rtData['token'];
            $refreshExpiresAt = $rtData['entity']->getExpiresAt();

            $response = $this->json([
                'token'      => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $accessTtl,
                'issued_at'  => date(DATE_ISO8601, $issuedAt),
                'expires_at' => date(DATE_ISO8601, $expiresAt),
                'user'       => [
                    'id'     => $user->getId(),
                    'email'  => $user->getEmail(),
                    'name'   => $user->getName(),
                    'roles'  => $user->getRoles(),
                    'active' => $user->isActive(),
                ],
            ]);
            $response->headers->setCookie($this->createRefreshCookie($refreshToken, $refreshExpiresAt));
            return $response;
        } catch (\Throwable $e) {
            $this->logger->error('Login error generating tokens', ['exception' => $e]);
            return $this->json(['error' => 'No se pudo generar los tokens.', 'code' => 500], 500);
        }
    }

    #[Route('/auth/token/refresh', name: 'api_auth_token_refresh', methods: ['POST'])]
    public function refreshToken(Request $request) : JsonResponse
    {
        $plainRefresh = $request->cookies->get('refresh_token');
        if (!$plainRefresh) {
            return $this->json(['error' => 'Refresh token ausente', 'code' => 401], 401);
        }
        try {
            $rotated = $this->refreshTokenService->rotate($plainRefresh);
            $user    = $rotated['entity']->getUser();
            if (!$user->isActive()) {
                throw new AuthenticationException('Usuario inactivo');
            }
            $accessTtl        = (int)$this->params->get('lexik_jwt_authentication.token_ttl');
            $issuedAt         = time();
            $expiresAt        = $issuedAt + $accessTtl;
            $accessToken      = $this->jwtManager->create($user);
            $newRefreshToken  = $rotated['token'];
            $refreshExpiresAt = $rotated['entity']->getExpiresAt();
            $response         = $this->json([
                'token'      => $accessToken,
                'token_type' => 'Bearer',
                'expires_in' => $accessTtl,
                'issued_at'  => date(DATE_ISO8601, $issuedAt),
                'expires_at' => date(DATE_ISO8601, $expiresAt),
                'user'       => [
                    'id'     => $user->getId(),
                    'email'  => $user->getEmail(),
                    'name'   => $user->getName(),
                    'roles'  => $user->getRoles(),
                    'active' => $user->isActive(),
                ],
            ]);
            $response->headers->setCookie($this->createRefreshCookie($newRefreshToken, $refreshExpiresAt));
            return $response;
        } catch (RefreshTokenInvalidException $e) {
            return $this->json(['error' => $e->getMessage(), 'code' => 401], 401);
        }
    }

    #[Route('/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request) : JsonResponse
    {
        $plainRefresh = $request->cookies->get('refresh_token');
        if ($plainRefresh) {
            try {
                $this->refreshTokenService->revokeChain($plainRefresh);
            } catch (\Throwable $e) { /* ignore */
            }
        }
        $response = $this->json(['message' => 'Logout OK']);
        $response->headers->setCookie($this->clearRefreshCookie());
        return $response;
    }
}
