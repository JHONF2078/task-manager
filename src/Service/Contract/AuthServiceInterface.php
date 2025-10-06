<?php

declare(strict_types=1);

namespace App\Service\Contract;

use App\Entity\User;

/**
 * Interfaz para el servicio de autenticación
 */
interface AuthServiceInterface
{
    /**
     * Registra un nuevo usuario.
     *
     * @param string $email
     * @param string $plainPassword
     * @param array  $roles
     * @param string $name
     *
     * @throws \App\Exception\ValidationException
     *
     * @return User|null
     */
    public function register(string $email, string $plainPassword, array $roles = ['ROLE_USER'], string $name = '') : ?User;

    /**
     * Autentica credenciales.
     *
     * @param string $email
     * @param string $plainPassword
     *
     * @throws \App\Exception\InvalidCredentialsException
     *
     * @return User
     */
    public function authenticate(string $email, string $plainPassword) : User;

    /**
     * Obtiene un usuario por email.
     *
     * @param string $email
     *
     * @return User|null
     */
    public function getUserByEmail(string $email) : ?User;
}
