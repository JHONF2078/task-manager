<?php
namespace App\Dto\auth;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Dto para la respuesta al registrar un nuevo usuario.
 */
class UserRegisterResponseDto
{
    /**
     * @Assert\NotNull()
     * @Assert\Type("integer")
     */
    public int $id;

    /**
     * @Assert\NotBlank()
     * @Assert\Email()
     */
    public string $email;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(min=2, max=100)
     */
    public string $name;

    /**
     * @Assert\NotNull()
     * @Assert\All({
     *     @Assert\NotBlank(),
     *     @Assert\Type("string")
     * })
     */
    public array $roles;

    public function __construct(int $id, string $email, string $name, array $roles)
    {
        $this->id = $id;
        $this->email = $email;
        $this->name = $name;
        $this->roles = $roles;
    }
}

