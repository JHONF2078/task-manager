<?php declare(strict_types=1);

namespace App\Mapper\Auto;

use App\Dto\auth\AuthResponseDto;
use App\Dto\UserResponseDto;
use App\Entity\User;
use Symfony\Component\Serializer\SerializerInterface;

class AuthResponseSerializerMapper
{
    /**
     * Build an array suitable for JSON response containing token info and serialized user data.
     *
     * @param User                $user
     * @param string              $accessToken
     * @param int                 $accessTtl
     * @param int                 $issuedAt
     * @param int                 $expiresAt
     * @param SerializerInterface $serializer
     *
     * @return array
     */
    public static function toArray(User $user, string $accessToken, int $accessTtl, int $issuedAt, int $expiresAt, SerializerInterface $serializer) : array
    {
        $userDto = $serializer->normalize($user, null, ['groups' => ['user:read']]);
        return [
            'token'      => $accessToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl,
            'issued_at'  => date(DATE_ATOM, $issuedAt),
            'expires_at' => date(DATE_ATOM, $expiresAt),
            'user'       => $userDto,
        ];
    }

    /**
     * Build an AuthResponseDto from an array (e.g. decoded JSON body).
     */
    public static function fromArray(array $data, SerializerInterface $serializer) : AuthResponseDto
    {
        $dto = new AuthResponseDto();

        $dto->token = (string)($data['token'] ?? '');
        $dto->token_type = (string)($data['token_type'] ?? 'Bearer');
        $dto->expires_in = isset($data['expires_in']) ? (int)$data['expires_in'] : 0;

        // issued_at and expires_at may be ISO strings or timestamps
        $issuedRaw = $data['issued_at'] ?? null;
        $expiresRaw = $data['expires_at'] ?? null;

        try {
            $dto->issued_at = self::parseDateTime($issuedRaw);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('issued_at inválido: ' . $e->getMessage());
        }

        try {
            $dto->expires_at = self::parseDateTime($expiresRaw);
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException('expires_at inválido: ' . $e->getMessage());
        }

        // Denormalize user part into UserResponseDto
        if (!isset($data['user'])) {
            throw new \InvalidArgumentException('user ausente en el payload');
        }

        /** @var UserResponseDto $userDto */
        $userDto = $serializer->denormalize($data['user'], UserResponseDto::class);
        $dto->user = $userDto;

        return $dto;
    }

    /**
     * Build an AuthResponseDto from a JSON string.
     */
    public static function fromJson(string $json, SerializerInterface $serializer) : AuthResponseDto
    {
        // Try using the serializer to deserialize directly to DTO; fallback to manual parse
        try {
            /** @var AuthResponseDto $dto */
            $dto = $serializer->deserialize($json, AuthResponseDto::class, 'json');
            if ($dto instanceof AuthResponseDto) {
                return $dto;
            }
        } catch (\Throwable) {
            // ignore and try manual fallback
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            throw new \InvalidArgumentException('JSON inválido para AuthResponseDto');
        }

        return self::fromArray($data, $serializer);
    }

    private static function parseDateTime($value) : \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }
        if (is_numeric($value)) {
            // treat as unix timestamp
            return new \DateTimeImmutable('@' . (string)$value);
        }
        if (is_string($value)) {
            return new \DateTimeImmutable($value);
        }
        throw new \InvalidArgumentException('Formato de fecha no reconocido');
    }
}
