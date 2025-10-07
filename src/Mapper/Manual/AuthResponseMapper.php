<?php declare(strict_types=1);

namespace App\Mapper\Manual;

use App\Dto\auth\AuthRequestDataDto;
use App\Dto\auth\AuthResponseDto;
use App\Dto\auth\UserResponseDto;
use DateTimeImmutable;

class AuthResponseMapper
{
    /**
     * Extrae los datos del usuario en array
     * @param $user
     * @return array<string, mixed>
     */
    private static function mapUser($user): array
    {
        return [
            'id'     => $user->getId(),
            'email'  => $user->getEmail(),
            'name'   => $user->getName(),
            'roles'  => $user->getRoles(),
            'active' => $user->isActive()
        ];
    }

    /**
     * Map input data from AuthRequestDataDto into an AuthResponseDto.
     *
     * @param AuthRequestDataDto $data
     *
     * @return AuthResponseDto
     */
    public static function toDto(AuthRequestDataDto $data) : AuthResponseDto
    {
        $dto             = new AuthResponseDto();
        $dto->token      = $data->accessToken;
        $dto->token_type = 'Bearer';
        $dto->expires_in = $data->accessTtl;
        $dto->issued_at  = new DateTimeImmutable('@' . (string)$data->issuedAt);
        $dto->expires_at = new DateTimeImmutable('@' . (string)$data->expiresAt);

        // Crear UserResponseDto usando el constructor con todos los argumentos requeridos
        $dto->user = new UserResponseDto(
            id: $data->user->getId(),
            email: $data->user->getEmail(),
            name: $data->user->getName(),
            roles: $data->user->getRoles(),
            active: $data->user->isActive()
        );

        return $dto;
    }

    /**
     * Converts input data from AuthRequestDataDto directly to an array.
     *
     * @param AuthRequestDataDto $data
     *
     * @return array<string, mixed>
     */
    public static function toArray(AuthRequestDataDto $data) : array
    {
        return [
            'token'      => $data->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $data->accessTtl,
            'issued_at'  => (new DateTimeImmutable('@' . (string)$data->issuedAt))->format(DateTimeImmutable::ATOM),
            'expires_at' => (new DateTimeImmutable('@' . (string)$data->expiresAt))->format(DateTimeImmutable::ATOM),
            'user'       => self::mapUser($data->user)
        ];
    }
}
