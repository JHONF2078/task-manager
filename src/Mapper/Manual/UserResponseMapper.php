<?php

declare(strict_types=1);

namespace App\Mapper\Manual;

use App\Dto\auth\UserResponseDto;
use App\Entity\User;

class UserResponseMapper
{
    /**
     * Convierte una entidad User a un DTO UserResponseDto
     */
    public static function toDto(User $user): UserResponseDto
    {
        return new UserResponseDto(
            id: $user->getId(),
            email: $user->getEmail(),
            name: $user->getName(),
            roles: $user->getRoles(),
            active: $user->isActive()
        );
    }

    /**
     * Convierte una entidad User directamente a un array
     */
    public static function toArray(User $user): array
    {
        return [
            'id'     => $user->getId(),
            'email'  => $user->getEmail(),
            'name'   => $user->getName(),
            'roles'  => $user->getRoles(),
            'active' => $user->isActive()
        ];
    }
}
