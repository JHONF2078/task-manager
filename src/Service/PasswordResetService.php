<?php
namespace App\Service;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Exception\ValidationException;

/**
 * Servicio dedicado a la gestión de tokens y flujo de reseteo de contraseña.
 * Centraliza la lógica que antes estaba repartida en AuthService y UserService.
 */
class PasswordResetService
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private PasswordHasherService $passwordHasher
    ) {}

    /**
     * Genera y persiste un token de reseteo para el usuario dado.
     */
    public function generateResetToken(User $user, int $ttlMinutes = 60): string
    {
        $token = bin2hex(random_bytes(32)); // 64 chars
        $expiresAt = new \DateTimeImmutable(sprintf('+%d minutes', $ttlMinutes));
        $user->setResetToken($token);
        $user->setResetTokenExpiresAt($expiresAt);
        $this->em->flush();
        return $token;
    }

    /**
     * Ejecuta el reseteo de contraseña si el token es válido.
     * Lanza ValidationException si la nueva contraseña no cumple requisitos.
     * Devuelve el usuario actualizado o null si el token no es válido / expirado.
     */
    public function resetPassword(string $token, string $newPassword): ?User
    {
        if (strlen($newPassword) < 6) {
            throw new ValidationException([], 'La contraseña debe tener al menos 6 caracteres');
        }
        $user = $this->userRepository->findOneBy(['resetToken' => $token]);
        if (!$user) { return null; }
        if (!$user->isResetTokenValid($token)) { return null; }
        $user->setPassword($this->passwordHasher->hash($newPassword));
        $user->setResetToken(null);
        $user->setResetTokenExpiresAt(null);
        $this->em->flush();
        return $user;
    }
}
