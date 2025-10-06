<?php declare(strict_types=1);

namespace App\Mapper\Manual;

use App\Dto\AuthResponseDto;
use App\Dto\UserResponseDto;
use App\Entity\User;
use DateTimeImmutable;

class AuthResponseMapper
{
    /**
     * Map a User entity and token metadata into an AuthResponseDto
     *
     * @param User   $user
     * @param string $accessToken
     * @param int    $accessTtl
     * @param int    $issuedAt    Unix timestamp
     * @param int    $expiresAt   Unix timestamp
     *
     * @return AuthResponseDto
     */
    public static function toDto(User $user, string $accessToken, int $accessTtl, int $issuedAt, int $expiresAt) : AuthResponseDto
    {
        $dto = new AuthResponseDto();
        $dto->token = $accessToken;
        $dto->token_type = 'Bearer';
        $dto->expires_in = $accessTtl;
        $dto->issued_at = new DateTimeImmutable('@' . (string)$issuedAt);
        $dto->expires_at = new DateTimeImmutable('@' . (string)$expiresAt);

        // Crear el UserResponseDto
        $userDto = new UserResponseDto();
        $userDto->id = $user->getId();
        $userDto->email = $user->getEmail();
        $userDto->name = $user->getName();
        $userDto->roles = $user->getRoles();
        $userDto->active = $user->isActive();

        $dto->user = $userDto;

        return $dto;
    }

    /**
     * Convierte directamente a un array con la estructura esperada por el frontend
     */
    public static function toArray(User $user, string $accessToken, int $accessTtl, int $issuedAt, int $expiresAt) : array
    {
        return [
            'token' => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
            'issued_at' => (new DateTimeImmutable('@' . (string)$issuedAt))->format(DateTimeImmutable::ATOM),
            'expires_at' => (new DateTimeImmutable('@' . (string)$expiresAt))->format(DateTimeImmutable::ATOM),
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'name' => $user->getName(),
                'roles' => $user->getRoles(),
                'active' => $user->isActive()
            ]
        ];
    }
}
