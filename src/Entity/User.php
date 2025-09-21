<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Este email ya está registrado')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private $id;

    #[ORM\Column(type: "string", length: 180, unique: true)]
    #[Assert\NotBlank(message: 'El email es obligatorio')]
    #[Assert\Email(message: 'Email inválido')]
    #[Assert\Length(max: 180, maxMessage: 'Email demasiado largo')]
    private $email;

    #[ORM\Column(type: "json")]
    private $roles = [];

    #[ORM\Column(type: "string")]
    private $password;

    // Campo temporal no persistido para validación en registro/cambio contraseña
    #[Assert\NotBlank(groups: ['register'], message: 'La contraseña es obligatoria')]
    #[Assert\Length(min: 6, minMessage: 'La contraseña debe tener al menos {{ limit }} caracteres', groups: ['register'])]
    private ?string $plainPassword = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\Length(max: 150, maxMessage: 'El nombre no puede exceder {{ limit }} caracteres')]
    private string $name = '';

    public function getId(): int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getUserIdentifier(): string
    {
        return $this->email;
    }

    // Compatibilidad con Symfony Security
    public function getUsername(): string
    {
        return $this->email;
    }

    public function getSalt(): ?string
    {
        // No se necesita salt con bcrypt/password_hash
        return null;
    }

    public function eraseCredentials(): void
    {
        // Si tienes datos temporales sensibles, límpialos aquí
        $this->plainPassword = null;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function deactivate(): void
    {
        $this->isActive = false;
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function activate(): void
    {
        $this->isActive = true;
        $this->deletedAt = null;
    }

    public function getDeletedAt(): ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): void
    {
        $this->resetToken = $resetToken;
    }

    public function getResetTokenExpiresAt(): ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $expiresAt): void
    {
        $this->resetTokenExpiresAt = $expiresAt;
    }

    public function isResetTokenValid(string $token): bool
    {
        if ($this->resetToken === null || $this->resetToken !== $token) {
            return false;
        }
        if ($this->resetTokenExpiresAt === null) {
            return false; // Si no hay expiración definida, considerar inválido por seguridad
        }
        return $this->resetTokenExpiresAt > new \DateTimeImmutable();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setPlainPassword(?string $plainPassword): void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }
}
