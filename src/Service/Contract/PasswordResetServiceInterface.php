<?php

declare(strict_types=1);

namespace App\Service\Contract;

use App\Entity\User;

/**
 * Interfaz para el servicio de reseteo de contraseña
 */
interface PasswordResetServiceInterface
{
    /**
     * Genera y persiste un token de reseteo para el usuario dado.
     * @param User $user
     * @param int $ttlMinutes
     * @return string
     */
    public function generateResetToken(User $user, int $ttlMinutes = 60): string;

    /**
     * Ejecuta el reseteo de contraseña si el token es válido.
     * @param string $token
     * @param string $newPassword
     * @return User|null
     * @throws \App\Exception\ValidationException
     */
    public function resetPassword(string $token, string $newPassword): ?User;
}

