<?php
namespace App\Entity;

use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RefreshTokenRepository::class)]
#[ORM\Table(name: 'refresh_token')]
#[ORM\UniqueConstraint(name: 'uniq_refresh_token_hash', columns: ['token_hash'])]
#[ORM\Index(name: 'idx_refresh_token_user', columns: ['user_id'])]
#[ORM\Index(name: 'idx_refresh_token_expires', columns: ['expires_at'])]
class RefreshToken
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'token_hash', type: 'string', length: 64, unique: true)]
    private string $tokenHash;

    #[ORM\Column(name: 'expires_at', type: 'datetime')]
    private \DateTimeInterface $expiresAt;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(name: 'revoked_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $revokedAt = null;

    #[ORM\Column(name: 'replaced_by', type: 'string', length: 64, nullable: true)]
    private ?string $replacedBy = null; // hash del nuevo token que lo sustituyó

    #[ORM\Column(name: 'last_used_at', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastUsedAt = null;

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function setUser(User $user): void { $this->user = $user; }
    public function getTokenHash(): string { return $this->tokenHash; }
    public function setTokenHash(string $hash): void { $this->tokenHash = $hash; }
    public function getExpiresAt(): \DateTimeInterface { return $this->expiresAt; }
    public function setExpiresAt(\DateTimeInterface $d): void { $this->expiresAt = $d; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $d): void { $this->createdAt = $d; }
    public function getRevokedAt(): ?\DateTimeInterface { return $this->revokedAt; }
    public function revoke(string $reason = ''): void { $this->revokedAt = new \DateTimeImmutable(); }
    public function isRevoked(): bool { return $this->revokedAt !== null; }
    public function isExpired(): bool { return $this->expiresAt <= new \DateTimeImmutable(); }
    public function getReplacedBy(): ?string { return $this->replacedBy; }
    public function setReplacedBy(?string $hash): void { $this->replacedBy = $hash; }
    public function getLastUsedAt(): ?\DateTimeInterface { return $this->lastUsedAt; }
    public function markUsed(): void { $this->lastUsedAt = new \DateTimeImmutable(); }
}

