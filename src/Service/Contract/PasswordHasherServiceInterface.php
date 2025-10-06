<?php

declare(strict_types=1);

namespace App\Service\Contract;

/**
 * Interfaz para el servicio de hashing de contraseñas
 */
interface PasswordHasherServiceInterface
{
    /**
     * Genera un hash a partir de una contraseña en texto plano.
     * @param string $plain
     * @return string
     */
    public function hash(string $plain): string;

    /**
     * Verifica si una contraseña en texto plano coincide con un hash.
     * @param string $plain
     * @param string $hash
     * @return bool
     */
    public function verify(string $plain, string $hash): bool;

    /**
     * Determina si un hash necesita ser regenerado.
     * @param string $hash
     * @return bool
     */
    public function needsRehash(string $hash): bool;
}

