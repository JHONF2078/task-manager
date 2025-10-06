<?php

declare(strict_types=1);

namespace App\Service\Contract;

use App\Entity\User;

/**
 * Interfaz para el servicio de envío de correos de reseteo de contraseña
 */
interface ResetPasswordMailServiceInterface
{
    /**
     * Envía el email de recuperación de contraseña.
     *
     * @param User   $user
     * @param string $token
     * @param string $resetUrl
     *
     * @return void
     */
    public function send(User $user, string $token, string $resetUrl) : void;
}
