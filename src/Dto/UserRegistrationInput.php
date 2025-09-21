<?php
namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;

class UserRegistrationInput
{
    #[Assert\NotBlank(message: 'El email es obligatorio')]
    #[Assert\Email(message: 'Email inválido')]
    #[Assert\Length(max:180, maxMessage: 'Email demasiado largo')]
    public string $email;

    #[Assert\NotBlank(message: 'La contraseña es obligatoria')]
    #[Assert\Length(min:6, minMessage: 'La contraseña debe tener al menos {{ limit }} caracteres')]
    public string $password;

    #[Assert\Length(max:150, maxMessage: 'El nombre no puede exceder {{ limit }} caracteres')]
    public ?string $name = null;
}
