<?php declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Exception\ConflictException;
use App\Exception\EntityNotFoundException;
use App\Exception\ValidationException;
use App\Service\AuthService;
use App\Service\UserService;
use App\Service\validation\UserValidationService;
use DateTimeInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class UserController extends AbstractController
{
    public function __construct(
        private readonly UserService           $userService,
        private readonly AuthService           $authService,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserValidationService $userValidationService,
    ) {
    }

    // 4. Perfil de usuario autenticado
    #[Route('/api/profile', name: 'api_user_profile', methods: ['GET'])]
    public function profile() : JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        $user  = $token?->getUser();
        if (!$user instanceof User) {
            throw new AuthenticationException('No autenticado');
        }
        return $this->json([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ]);
    }

    // 5. Listado de usuarios (solo admin)
    #[Route('/api/users', name: 'api_users_list', methods: ['GET'])]
    public function list(Request $request) : JsonResponse
    {
        $token = $this->tokenStorage->getToken();
        $user  = $token?->getUser();
        if (!$user instanceof User) {
            throw new AuthenticationException('No autenticado');
        }
        $email = $request->query->get('email');
        $users = $this->userService->listUsers($email);
        $data  = array_map(fn ($u) => [
            'id'        => $u->getId(),
            'name'      => $u->getName(),
            'email'     => $u->getEmail(),
            'roles'     => $u->getRoles(),
            'active'    => $u->isActive(),
            'deletedAt' => $u->getDeletedAt() ? $u->getDeletedAt()->format(DateTimeInterface::ATOM) : null,
        ], $users);
        return $this->json($data);
    }

    // NUEVO: Crear usuario (solo admin)
    #[Route('/api/users', name: 'api_user_create', methods: ['POST'])]
    public function create(Request $request) : JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $data = json_decode($request->getContent(), true) ?? [];

        try {
            $validatedData = $this->userValidationService->validateAndNormalizeUserData($data);
            $roles         = $this->userValidationService->normalizeRoles($data['roles'] ?? null);

            $created = $this->authService->register(
                $validatedData['normalizedData']['email'],
                $validatedData['normalizedData']['password'],
                $roles,
                $validatedData['normalizedData']['name'] ?? ''
            );

            if (!$created) {
                throw new ConflictException('Email ya registrado');
            }
        } catch (ConflictException $ce) {
            return $this->json(['error' => $ce->getMessage()], 409);
        }

        return $this->json([
            'id'        => $created->getId(),
            'name'      => $created->getName(),
            'email'     => $created->getEmail(),
            'roles'     => $created->getRoles(),
            'active'    => $created->isActive(),
            'deletedAt' => $created->getDeletedAt()?->format(DateTimeInterface::ATOM),
        ], 201);
    }

    // 6. Edición de usuario (solo admin)
    #[Route('/api/users/{id}', name: 'api_user_update', methods: ['PUT'])]
    public function update(int $id, Request $request) : JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->userService->getUserById($id);
        if (!$user) {
            throw new EntityNotFoundException('Usuario', $id);
        }
        $data = json_decode($request->getContent(), true);
        try {
            $user = $this->userService->updateUser($user, $data ?? []);
        } catch (ValidationException $ve) {
            return $this->json(['error' => $ve->getMessage(), 'violations' => $ve->getViolations()], 400);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage(), 'violations' => []], 400);
        }
        return $this->json([
            'id'    => $user->getId(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles()
        ]);
    }

    // 7. Eliminación de usuario (solo admin)
    #[Route('/api/users/{id}', name: 'api_user_delete', methods: ['DELETE'])]
    public function delete(int $id) : JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->userService->getUserById($id);
        if (!$user) {
            throw new EntityNotFoundException('Usuario', $id);
        }
        $this->userService->deleteUser($user);
        return $this->json([
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'roles'     => $user->getRoles(),
            'active'    => $user->isActive(),
            'deletedAt' => $user->getDeletedAt()?->format(DateTimeInterface::ATOM),
        ]);
    }

    // 10. Actualización de roles (solo admin)
    #[Route('/api/users/{id}/roles', name: 'api_user_roles_update', methods: ['POST'])]
    public function updateRoles(int $id, Request $request) : JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $target = $this->userService->getUserById($id);
        if (!$target) {
            throw new EntityNotFoundException('Usuario', $id);
        }
        $data  = json_decode($request->getContent(), true);
        $roles = $data['roles'] ?? null;
        if (!is_array($roles)) {
            throw new ValidationException([], 'Formato inválido. Se espera { "roles": ["ROLE_X", ...] }');
        }
        $this->userService->updateRoles($target, $roles);
        return $this->json([
            'id'    => $target->getId(),
            'email' => $target->getEmail(),
            'roles' => $target->getRoles()
        ]);
    }

    // 11. Activar/desactivar usuario (solo admin)
    #[Route('/api/users/{id}/status', name: 'api_user_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request) : JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $user = $this->userService->getUserById($id);
        if (!$user) {
            throw new EntityNotFoundException('Usuario', $id);
        }
        $data = json_decode($request->getContent(), true);
        if (!isset($data['active'])) {
            return $this->json(['error' => 'Falta el campo "active" (boolean)'], 400);
        }
        $active = (bool)$data['active'];
        try {
            if ($active) {
                $this->userService->activateUser($user);
            } else {
                $this->userService->deactivateUser($user);
            }
        } catch (RuntimeException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
        return $this->json([
            'id'        => $user->getId(),
            'email'     => $user->getEmail(),
            'roles'     => $user->getRoles(),
            'active'    => $user->isActive(),
            'deletedAt' => $user->getDeletedAt()?->format(DateTimeInterface::ATOM),
        ]);
    }
}
