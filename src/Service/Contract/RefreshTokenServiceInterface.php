<?php

declare(strict_types=1);

namespace App\Service\Contract;

use App\Entity\RefreshToken;
use App\Entity\User;

/**
 * Interfaz para el servicio de gestión de refresh tokens
 */
interface RefreshTokenServiceInterface
{
    /**
     * Crea un nuevo refresh token para el usuario.
     * @param User $user
     * @return array{entity: RefreshToken, token: string}
     */
    public function create(User $user): array;

    /**
     * Rota un refresh token existente.
     * @param string $plainToken
     * @return array{entity: RefreshToken, token: string}
     * @throws \App\Exception\RefreshTokenInvalidException
     */
    public function rotate(string $plainToken): array;

    /**
     * Valida y obtiene un refresh token.
     * @param string $plainToken
     * @param bool $forRotation
     * @return RefreshToken
     * @throws \App\Exception\RefreshTokenInvalidException
     */
    public function validateAndGet(string $plainToken, bool $forRotation = false): RefreshToken;
}

