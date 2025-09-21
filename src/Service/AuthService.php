<?php

namespace App\Service;

use App\Entity\User;
use App\Exception\InvalidCredentialsException;
use App\Repository\AuthRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Exception\ValidationException;

/**
 * Servicio de autenticación y funcionalidades relacionadas (registro, login, reset password).
 * Separa la lógica de negocio de acceso a datos (AuthRepository) y deja a los controladores
 * solo la orquestación HTTP.
 */
class AuthService
{
    public function __construct(
        private AuthRepository $authRepository,
        private PasswordHasherService $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    /**
     * Registra un nuevo usuario. Devuelve el usuario creado o null si el email ya existe (conflicto)
     * Lanza ValidationException si fallan constraints.
     */
    public function register(string $email, string $plainPassword, array $roles = ['ROLE_USER'], string $name = ''): ?User
    {
        if ($this->authRepository->isEmailTaken($email)) {
            return null; // conflicto
        }
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setPlainPassword($plainPassword);
        $user->setName($name !== '' ? $name : (explode('@', $email)[0] ?? ''));

        $violations = $this->validator->validate($user, null, ['register']);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()];
            }
            throw new ValidationException($errors);
        }
        // Hash tras validación
        $user->setPassword($this->passwordHasher->hash($plainPassword));
        $user->eraseCredentials();
        $this->authRepository->save($user);
        return $user;
    }

    /**
     * Autentica credenciales. Lanza InvalidCredentialsException si son inválidas.
     */
    public function authenticate(string $email, string $plainPassword): User
    {
        $user = $this->authRepository->findByEmail($email);
        if (!$user || !$this->passwordHasher->verify($plainPassword, $user->getPassword())) {
            throw new InvalidCredentialsException();
        }
        // Rehash adaptativo si el algoritmo/coste cambian
        if ($this->passwordHasher->needsRehash($user->getPassword())) {
            $user->setPassword($this->passwordHasher->hash($plainPassword));
            $this->authRepository->save($user); // persist rehash
        }
        return $user;
    }

    public function getUserByEmail(string $email): ?User
    {
        return $this->authRepository->findByEmail($email);
    }
}
