<?php

declare(strict_types=1);

namespace App\Mapper\Manual;

use App\Dto\UserResponseDto;
use App\Entity\User;

class UserResponseMapper
{
    public static function toArray(User $user) : UserResponseDto
    {
        $dto         = new UserResponseDto();
        $dto->id     = $user->getId();
        $dto->email  = $user->getEmail();
        $dto->name   = $user->getName();
        $dto->roles  = $user->getRoles();
        $dto->active = $user->isActive();
        return $dto;
    }
}
