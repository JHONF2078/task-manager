<?php declare(strict_types=1);

namespace App\Service;

use App\Entity\RefreshToken;
use App\Entity\User;
use App\Exception\RefreshTokenInvalidException;
use App\Repository\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

class RefreshTokenService
{
    public function __construct(
        private RefreshTokenRepository $repo,
        private EntityManagerInterface $em,
    ) {
    }

    private function ttlSeconds() : int
    {
        return (int)($_ENV['REFRESH_TOKEN_TTL'] ?? 604800); // 7 días por defecto
    }

    public function create(User $user) : array
    {
        $plain = $this->generatePlainToken();
        $hash  = hash('sha256', $plain);
        $rt    = new RefreshToken();
        $rt->setUser($user);
        $rt->setTokenHash($hash);
        $rt->setCreatedAt(new \DateTimeImmutable());
        $rt->setExpiresAt(new \DateTimeImmutable('+' . $this->ttlSeconds() . ' seconds'));
        $this->em->persist($rt);
        $this->em->flush();
        return [ 'entity' => $rt, 'token' => $plain ];
    }

    public function rotate(string $plainToken) : array
    {
        $original = $this->validateAndGet($plainToken, true);
        // Marcar revocado y crear nuevo
        $original->revoke();
        $newPlain = $this->generatePlainToken();
        $newHash  = hash('sha256', $newPlain);
        $original->setReplacedBy($newHash);
        $new = new RefreshToken();
        $new->setUser($original->getUser());
        $new->setTokenHash($newHash);
        $new->setCreatedAt(new \DateTimeImmutable());
        $new->setExpiresAt(new \DateTimeImmutable('+' . $this->ttlSeconds() . ' seconds'));
        $this->em->persist($new);
        $this->em->flush();
        return [ 'entity' => $new, 'token' => $newPlain ];
    }

    public function validateAndGet(string $plainToken, bool $forRotation = false) : RefreshToken
    {
        $hash = hash('sha256', $plainToken);
        $rt   = $this->repo->findOneBy(['tokenHash' => $hash]);
        if (!$rt) {
            throw new RefreshTokenInvalidException('Refresh token no encontrado');
        }
        if ($rt->isExpired()) {
            throw new RefreshTokenInvalidException('Refresh token expirado');
        }
        if ($rt->isRevoked()) {
            // Re-uso de token rotado
            throw new RefreshTokenInvalidException('Refresh token ya revocado / reutilizado');
        }
        if ($forRotation) {
            $rt->markUsed();
        }
        return $rt;
    }

    public function revokeChain(string $plainToken) : void
    {
        $hash = hash('sha256', $plainToken);
        $rt   = $this->repo->findOneBy(['tokenHash' => $hash]);
        if ($rt) {
            $rt->revoke();
            $this->em->flush();
        }
    }

    private function generatePlainToken() : string
    {
        // 32 bytes -> base64url sin relleno (longitud ~43)
        $raw = random_bytes(32);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }
}
