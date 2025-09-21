<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Exception\InvalidCredentialsException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ValidationException;

class UserService
{
    private array $allowedRoles = ['ROLE_USER','ROLE_ADMIN'];

    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private PasswordHasherService $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    public function register(string $email, string $plainPassword, array $roles = ['ROLE_USER'], string $name = ''): ?User
    {
        if ($this->userRepository->findOneBy(['email' => $email])) {
            return null; // Email ya registrado
        }
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPlainPassword($plainPassword);
        $user->setName($name !== '' ? $name : (explode('@',$email)[0] ?? ''));
        $this->validateUser($user, ['register']);
        $user->setPassword($this->passwordHasher->hash($plainPassword));
        $user->eraseCredentials();
        $this->em->persist($user);
        $this->em->flush();
        return $user;
    }

    /**
     * Autentica un usuario. Lanza InvalidCredentialsException si email o password no son válidos.
     */
    public function authenticate(string $email, string $plainPassword): User
    {
        $user = $this->userRepository->findOneBy(['email' => $email]);
        if (!$user || !$this->passwordHasher->verify($plainPassword, $user->getPassword())) {
            throw new InvalidCredentialsException();
        }
        if ($this->passwordHasher->needsRehash($user->getPassword())) {
            $user->setPassword($this->passwordHasher->hash($plainPassword));
            $this->em->flush();
        }
        return $user;
    }

    public function getUserById(int $id): ?User { return $this->userRepository->find($id); }
    public function getUserByEmail(string $email): ?User { return $this->userRepository->findOneBy(['email' => $email]); }

    public function listUsers(?string $email = null): array
    {
        $qb = $this->createListUsersQuery($email);
        return $qb->getQuery()->getResult();
    }

    public function listUsersWithExplain(?string $email = null): array
    {
        $qb = $this->createListUsersQuery($email);
        $query = $qb->getQuery();
        $users = $query->getResult();

        $sql   = $query->getSQL(); // SQL con placeholders
        $params = [];
        foreach ($query->getParameters() as $p) { $params[] = $p->getValue(); }

        $conn = $this->em->getConnection();
        $platform = $conn->getDatabasePlatform()->getName();
        $prefix = 'EXPLAIN ';
        if (stripos($platform, 'sqlite') !== false) {
            $prefix = 'EXPLAIN QUERY PLAN ';
        } elseif (stripos($platform, 'postgres') !== false) {
            $prefix = 'EXPLAIN ';
        }

        try {
            $explainRows = $conn->fetchAllAssociative($prefix . $sql, $params);
        } catch (\Throwable $e) {
            $explainRows = [['error' => $e->getMessage()]];
        }

        return [
            'users' => $users,
            'explain' => [
                'platform' => $platform,
                'sql' => $sql,
                'params' => $params,
                'rows' => $explainRows,
            ],
        ];
    }

    private function createListUsersQuery(?string $email = null)
    {
        $qb = $this->userRepository->createQueryBuilder('u');
        $email = $email !== null ? trim($email) : null;
        if ($email !== null && $email !== '') {
            $qb->andWhere('u.email LIKE :email')
               ->setParameter('email', '%' . $email . '%');
        }
        $qb->orderBy('u.id', 'ASC');
        return $qb;
    }

    public function updateUser(User $user, array $data): User
    {
        $emailChanged = false;
        if (isset($data['email']) && $data['email'] !== $user->getEmail()) {
            $user->setEmail($data['email']);
            $emailChanged = true;
        }
        if (isset($data['roles'])) { $this->applyRoles($user, $data['roles']); }
        if (isset($data['password']) && $data['password'] !== '') {
            $user->setPlainPassword($data['password']);
        }
        if (isset($data['name'])) { $user->setName($data['name']); }
        // Validar (grupo register si se cambia password para reutilizar reglas de contraseña)
        $groups = isset($data['password']) && $data['password'] !== '' ? ['register'] : null;
        $this->validateUser($user, $groups);
        if ($user->getPlainPassword()) {
            $user->setPassword($this->passwordHasher->hash($user->getPlainPassword()));
            $user->eraseCredentials();
        }
        $this->em->flush();
        return $user;
    }

    private function validateUser(User $user, ?array $groups = null): void
    {
        $violations = $this->validator->validate($user, null, $groups);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()];
            }
            throw new ValidationException($errors);
        }
    }

    public function updateRoles(User $user, array $roles): User
    {
        $this->applyRoles($user, $roles); $this->em->flush(); return $user;
    }

    private function applyRoles(User $user, array $roles): void
    {
        $roles = array_values(array_unique($roles));
        if (empty($roles)) { throw new \InvalidArgumentException('La lista de roles no puede estar vacía'); }
        foreach ($roles as $r) {
            if (!in_array($r, $this->allowedRoles, true)) {
                throw new \InvalidArgumentException(sprintf('Rol no permitido: %s', $r));
            }
        }
        $isRemovingAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true) && !in_array('ROLE_ADMIN', $roles, true);
        if ($isRemovingAdmin && $this->userRepository->isLastAdmin($user)) {
            throw new \RuntimeException('No se puede remover ROLE_ADMIN: es el último administrador.');
        }
        $user->setRoles($roles);
    }

    public function deleteUser(User $user): void
    {
        if ($this->userRepository->isLastAdmin($user)) {
            throw new \RuntimeException('No se puede desactivar: es el último administrador activo.');
        }
        if ($user->isActive()) { $user->deactivate(); $this->em->flush(); }
    }

    public function deactivateUser(User $user): User
    {
        if (!$user->isActive()) { return $user; }
        if ($this->userRepository->isLastAdmin($user)) { throw new \RuntimeException('No se puede desactivar: es el último administrador activo.'); }
        $user->deactivate(); $this->em->flush(); return $user;
    }

    public function activateUser(User $user): User
    { if ($user->isActive()) { return $user; } $user->activate(); $this->em->flush(); return $user; }
}
