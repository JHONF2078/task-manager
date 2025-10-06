<?php declare(strict_types=1);

namespace App\Service\Contract;

use App\Entity\User;

interface UserServiceInterface
{
    /**
     * Registra un nuevo usuario.
     *
     * @param string $email
     * @param string $plainPassword
     * @param array $roles
     * @param string $name
     * @return User|null Retorna null si el email ya está registrado
     */
    public function register(string $email, string $plainPassword, array $roles = ['ROLE_USER'], string $name = ''): ?User;

    /**
     * Autentica un usuario. Lanza InvalidCredentialsException si email o password no son válidos.
     *
     * @param string $email
     * @param string $plainPassword
     * @return User
     */
    public function authenticate(string $email, string $plainPassword): User;

    /**
     * Obtiene un usuario por ID.
     *
     * @param int $id
     * @return User|null
     */
    public function getUserById(int $id): ?User;

    /**
     * Obtiene un usuario por email.
     *
     * @param string $email
     * @return User|null
     */
    public function getUserByEmail(string $email): ?User;

    /**
     * Lista todos los usuarios, opcionalmente filtrados por email.
     *
     * @param string|null $email
     * @return array
     */
    public function listUsers(?string $email = null): array;

    /**
     * Lista usuarios con información de EXPLAIN de la consulta.
     *
     * @param string|null $email
     * @return array
     */
    public function listUsersWithExplain(?string $email = null): array;

    /**
     * Actualiza los datos de un usuario.
     *
     * @param User $user
     * @param array $data
     * @return User
     */
    public function updateUser(User $user, array $data): User;

    /**
     * Actualiza los roles de un usuario.
     *
     * @param User $user
     * @param array $roles
     * @return User
     */
    public function updateRoles(User $user, array $roles): User;

    /**
     * Elimina (desactiva) un usuario.
     *
     * @param User $user
     * @return void
     */
    public function deleteUser(User $user): void;

    /**
     * Desactiva un usuario.
     *
     * @param User $user
     * @return User
     */
    public function deactivateUser(User $user): User;

    /**
     * Activa un usuario.
     *
     * @param User $user
     * @return User
     */
    public function activateUser(User $user): User;
}

