<?php

declare(strict_types=1);

namespace App\Dto\auth;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO para la respuesta de usuario (usado en registro, login, y otras operaciones)
 */
class UserResponseDto
{
    public function __construct(
        #[Groups(['user:read', 'auth:read'])]
        #[Assert\NotNull]
        #[Assert\Type("integer")]
        public readonly int $id,

        #[Groups(['user:read', 'auth:read'])]
        #[Assert\NotBlank]
        #[Assert\Email]
        public readonly string $email,

        #[Groups(['user:read', 'auth:read'])]
        #[Assert\Length(min: 2, max: 100)]
        public readonly ?string $name,

        #[Groups(['user:read', 'auth:read'])]
        #[Assert\NotNull]
        #[Assert\All([
            new Assert\NotBlank(),
            new Assert\Type("string")
        ])]
        public readonly array $roles,

        #[Groups(['user:read', 'auth:read'])]
        #[Assert\NotNull]
        #[Assert\Type("bool")]
        public readonly bool $active,
    ) {
    }
}
