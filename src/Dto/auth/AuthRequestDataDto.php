<?php declare(strict_types=1);

namespace App\Dto\auth;

use App\Entity\User;

/**
 * Para agrupar todos los datos de entrada para el mapeador de autenticación.
 * es decir enviar un solo objeto al mapeador en lugar de múltiples parámetros.
 */
readonly class AuthRequestDataDto
{
    public function __construct(
        public User   $user,
        public string $accessToken,
        public int    $accessTtl,
        public int    $issuedAt,
        public int    $expiresAt
    ) {
    }
}