<?php declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Este email ya está registrado', groups: ['Persist'])]
class User implements UserInterface
{
    public const ROLE_USER  = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    public const ALLOWED_ROLES = [
        self::ROLE_USER,
        self::ROLE_ADMIN,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    #[Groups(['task:read'])]
    private $id;

    #[ORM\Column(type: "string", length: 180, unique: true)]
    #[Groups(['task:read'])]
    private $email;

    #[ORM\Column(type: "json")]
    #[Groups(['task:read'])]
    private $roles = [];

    #[ORM\Column(type: "string")]
    private $password;

    // Campo temporal no persistido para validación en registro/cambio contraseña
    private ?string $plainPassword = null;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['task:read'])]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetTokenExpiresAt = null;

    #[ORM\Column(type: 'string', length: 150)]
    private string $name = '';

    public function getId() : int
    {
        return $this->id;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    public function setEmail(string $email) : void
    {
        $this->email = $email;
    }

    public function getRoles() : array
    {
        return $this->roles;
    }

    public function setRoles(array $roles) : void
    {
        $this->roles = $roles;
    }

    public function getPassword() : string
    {
        return $this->password;
    }

    public function setPassword(string $password) : void
    {
        $this->password = $password;
    }

    public function getUserIdentifier() : string
    {
        return $this->email;
    }

    // Compatibilidad con Symfony Security
    public function getUsername() : string
    {
        return $this->email;
    }

    public function getSalt() : ?string
    {
        // No se necesita salt con bcrypt/password_hash
        return null;
    }

    public function eraseCredentials() : void
    {
        // Si tienes datos temporales sensibles, límpialos aquí
        $this->plainPassword = null;
    }

    public function isActive() : bool
    {
        return $this->isActive;
    }

    public function deactivate() : void
    {
        $this->isActive  = false;
        $this->deletedAt = new \DateTimeImmutable();
    }

    public function activate() : void
    {
        $this->isActive  = true;
        $this->deletedAt = null;
    }

    public function getDeletedAt() : ?\DateTimeInterface
    {
        return $this->deletedAt;
    }

    public function getResetToken() : ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken) : void
    {
        $this->resetToken = $resetToken;
    }

    public function getResetTokenExpiresAt() : ?\DateTimeInterface
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTimeInterface $expiresAt) : void
    {
        $this->resetTokenExpiresAt = $expiresAt;
    }

    public function isResetTokenValid(string $token) : bool
    {
        if ($this->resetToken === null || $this->resetToken !== $token) {
            return false;
        }
        if ($this->resetTokenExpiresAt === null) {
            return false; // Si no hay expiración definida, considerar inválido por seguridad
        }
        return $this->resetTokenExpiresAt > new \DateTimeImmutable();
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function setPlainPassword(?string $plainPassword) : void
    {
        $this->plainPassword = $plainPassword;
    }

    public function getPlainPassword() : ?string
    {
        return $this->plainPassword;
    }
}
