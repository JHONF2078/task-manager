<?php declare(strict_types=1);

namespace App\Dto\auth;

use DateTimeImmutable;

/**
 * Dto para la respuesta de autenticación al momento de hacer login.
 */
class AuthResponseDto
{
    public string $token;
    public string $token_type;
    public int $expires_in;
    public DateTimeImmutable $issued_at;
    public DateTimeImmutable $expires_at;
    public UserResponseDto $user;
}

