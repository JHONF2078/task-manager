<?php declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

class AuthResponseDto
{
    #[Groups(['auth:read'])]
    public string $token;

    #[Groups(['auth:read'])]
    public string $token_type = 'Bearer';

    #[Groups(['auth:read'])]
    public int $expires_in;

    #[Groups(['auth:read'])]
    public \DateTimeImmutable $issued_at;

    #[Groups(['auth:read'])]
    public \DateTimeImmutable $expires_at;

    #[Groups(['auth:read'])]
    public UserResponseDto $user;
}

