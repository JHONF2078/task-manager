<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

class UserResponseDto
{
    #[Groups(['user:read', 'auth:read'])]
    public int $id;

    #[Groups(['user:read', 'auth:read'])]
    public string $email;

    #[Groups(['user:read', 'auth:read'])]
    public ?string $name;

    #[Groups(['user:read', 'auth:read'])]
    public array $roles;

    #[Groups(['user:read', 'auth:read'])]
    public bool $active;
}
