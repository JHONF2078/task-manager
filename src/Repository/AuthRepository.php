<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class AuthRepository
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    private function repo(): ObjectRepository
    {
        return $this->em->getRepository(User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->repo()->findOneBy(['email' => $email]);
    }

    public function isEmailTaken(string $email): bool
    {
        return (bool)$this->findByEmail($email);
    }

    public function save(User $user, bool $flush = true): void
    {
        $this->em->persist($user);
        if ($flush) { $this->em->flush(); }
    }

    public function findByResetToken(string $token): ?User
    {
        return $this->repo()->findOneBy(['resetToken' => $token]);
    }

    public function flush(): void
    {
        $this->em->flush();
    }
}

