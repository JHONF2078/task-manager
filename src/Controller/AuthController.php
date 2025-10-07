<?php declare(strict_types=1);

namespace App\Controller;

use App\Dto\auth\AuthRequestDataDto;
use App\Dto\auth\AuthResponseDto;
use App\Dto\auth\UserResponseDto;
use App\Exception\ConflictException;
use App\Exception\InvalidCredentialsException;
use App\Exception\RefreshTokenInvalidException;
use App\Exception\ValidationException;
use App\Helper\MapperHelper;
use App\Mapper\Manual\AuthResponseMapper;
use App\Service\Contract\AuthServiceInterface;
use App\Service\RefreshTokenService;
use App\Service\validation\UserValidationService;
use DateTimeImmutable;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Throwable;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly AuthServiceInterface     $authService,
        private readonly JWTTokenManagerInterface $jwtManager,
        private readonly ParameterBagInterface    $params,
        private readonly LoggerInterface          $logger,
        private readonly RefreshTokenService      $refreshTokenService,
        private readonly UserValidationService    $userValidationService,
        private readonly MapperHelper             $mapperHelper,
    ) {
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request) : JsonResponse
    {
        $data     = json_decode($request->getContent(), true) ?? [];
        $email    = $data['email']                            ?? '';
        $password = $data['password']                         ?? '';

        try {
            $user = $this->authService->authenticate($email, $password);

            if (!$user->isActive()) {
                throw new AuthenticationException('Usuario inactivo');
            }

            $accessTtl        = (int)$this->params->get('lexik_jwt_authentication.token_ttl');
            $issuedAt         = time();
            $expiresAt        = $issuedAt + $accessTtl;
            $accessToken      = $this->jwtManager->create($user);
            $rtData           = $this->refreshTokenService->create($user);
            $refreshToken     = $rtData['token'];
            $refreshExpiresAt = $rtData['entity']->getExpiresAt();

            return $this->buildAuthResponse(
                $accessToken,
                $accessTtl,
                $issuedAt,
                $expiresAt,
                $user,
                $refreshToken,
                $refreshExpiresAt
            );
        } catch (Throwable $e) {
            // Loguear errores inesperados, pero dejar que el subscriber maneje la respuesta
            if (!($e instanceof InvalidCredentialsException || $e instanceof AuthenticationException)) {
                $this->logger->error('Login error generating tokens', ['exception' => $e]);
            }
            throw $e;
        }
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request) : JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $validatedData = $this->userValidationService->validateAndNormalizeUserData($data);
            $roles         = $this->userValidationService->normalizeRoles();

            $user = $this->authService->register(
                $validatedData['email'],
                $validatedData['password'],
                $roles,
                $validatedData['name'] ?? ''
            );

            if (!$user) {
                throw new ConflictException('Email ya registrado');
            }

            $dto = $this->mapperHelper->map($user, UserResponseDto::class, MapperHelper::STRATEGY_AUTO_MAPPER);

            return $this->json($dto, 201);

        } catch (Throwable $e) {
            if (!($e instanceof ValidationException || $e instanceof ConflictException)) {
                $this->logger->error('Error en registro: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    #[Route('/auth/token/refresh', name: 'api_auth_token_refresh', methods: ['POST'])]
    public function refreshToken(Request $request) : JsonResponse
    {
        $plainRefresh = $request->cookies->get('refresh_token');
        if (!$plainRefresh) {
            throw new AuthenticationException('Refresh token ausente');
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

            return $this->buildAuthResponse(
                $accessToken,
                $accessTtl,
                $issuedAt,
                $expiresAt,
                $user,
                $newRefreshToken,
                $refreshExpiresAt
            );
        } catch (Throwable $e) {
            if (!($e instanceof RefreshTokenInvalidException || $e instanceof AuthenticationException)) {
                $this->logger->error('Error en refresh token: ' . $e->getMessage());
            }
            throw $e;
        }
    }

    #[Route('/auth/logout', name: 'api_auth_logout', methods: ['POST'])]
    public function logout(Request $request) : JsonResponse
    {
        $plainRefresh = $request->cookies->get('refresh_token');
        if ($plainRefresh) {
            try {
                $this->refreshTokenService->revokeChain($plainRefresh);
            } catch (Throwable) { /* ignore */
            }
        }
        $response = $this->json(['message' => 'Logout OK']);
        $response->headers->setCookie($this->clearRefreshCookie());
        return $response;
    }

    private function buildAuthResponse(
        string $accessToken,
        int $accessTtl,
        int $issuedAt,
        int $expiresAt,
        $user,
        string $refreshToken,
        $refreshExpiresAt
    ) : JsonResponse {
        $authDataDto = new AuthRequestDataDto($user, $accessToken, $accessTtl, $issuedAt, $expiresAt);


//        $payload = $this->mapperHelper->map(
//            $authDataDto,
//            AuthResponseDto::class,
//            MapperHelper::STRATEGY_AUTO_MAPPER
//        );


        $payload = $this->mapperHelper->map(
            $authDataDto,
            AuthResponseDto::class,
            MapperHelper::STRATEGY_MANUAL_MAPPER,
            AuthResponseMapper::class,
            'toDto'
        );

        $response = $this->json($payload);
        $response->headers->setCookie($this->createRefreshCookie($refreshToken, $refreshExpiresAt));
        return $response;
    }

    private function createRefreshCookie(string $token, DateTimeImmutable $expiresAt) : Cookie
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
            ->withExpires((new DateTimeImmutable('-1 hour')));
    }
}
