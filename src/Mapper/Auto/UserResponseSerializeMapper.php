<?php declare(strict_types=1);

namespace App\Mapper\Auto;

use App\Entity\User;
use Symfony\Component\Serializer\SerializerInterface;

class UserResponseSerializeMapper
{
    /**
     * Map a User entity into an array suitable for JSON response using the serializer.
     *
     * @param User                $user
     * @param SerializerInterface $serializer
     *
     * @return array
     */
    public static function toArray(User $user, SerializerInterface $serializer) : array
    {
        return $serializer->normalize($user, null, ['groups' => ['user:read']]);
    }

    /**
     * Denormalize an array into a User entity using the serializer.
     *
     * @param array               $data
     * @param SerializerInterface $serializer
     *
     * @return User
     */
    public static function fromArray(array $data, SerializerInterface $serializer) : User
    {
        /** @var User $user */
        $user = $serializer->denormalize($data, User::class);
        return $user;
    }

    /**
     * Deserialize a JSON string into a User entity using the serializer.
     *
     * @param string              $json
     * @param SerializerInterface $serializer
     *
     * @return User
     */
    public static function fromJson(string $json, SerializerInterface $serializer) : User
    {
        /** @var User $user */
        $user = $serializer->deserialize($json, User::class, 'json');
        return $user;
    }
}
