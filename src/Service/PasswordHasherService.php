<?php

namespace App\Service;

/**
 * Servicio central para hashing y verificación de contraseñas.
 * Permite centralizar algoritmo y facilitar futura migración (ej: Argon2id) o rehash adaptativo.
 */
class PasswordHasherService
{
    private string|int $algo; // antes int, ahora permite string|int según versión de PHP
    private array $options;

    /**
     * @param int|string|null $algo Puede ser constante PASSWORD_*, 'auto', 'bcrypt', 'argon2i', 'argon2id' o null
     */
    public function __construct(int|string|null $algo = null, array $options = [])
    {
        $this->algo = $this->normalizeAlgo($algo);
        $this->options = $options;
    }

    private function normalizeAlgo(int|string|null $algo): int|string
    {
        if (is_int($algo) || (is_string($algo) && $algo !== '')) { return $algo; }
        if ($algo === null || $algo === '' || $algo === 'auto' || $algo === 'default') {
            return PASSWORD_DEFAULT; // Puede ser string o int según versión
        }
        $map = [
            'bcrypt' => defined('PASSWORD_BCRYPT') ? PASSWORD_BCRYPT : PASSWORD_DEFAULT,
            'argon2i' => defined('PASSWORD_ARGON2I') ? PASSWORD_ARGON2I : PASSWORD_DEFAULT,
            'argon2id' => defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT,
        ];
        $key = strtolower((string)$algo);
        return $map[$key] ?? PASSWORD_DEFAULT;
    }

    public function hash(string $plain): string
    { return password_hash($plain, $this->algo, $this->options); }
    public function verify(string $plain, string $hash): bool
    { return password_verify($plain, $hash); }
    public function needsRehash(string $hash): bool
    { return password_needs_rehash($hash, $this->algo, $this->options); }
}
