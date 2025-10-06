<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Repository\Contract\AuthRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;

class AuthRepository implements AuthRepositoryInterface
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    private function repo() : ObjectRepository
    {
        return $this->em->getRepository(User::class);
    }

    // Métodos estándar de Doctrine
    public function find($id, $lockMode = null, $lockVersion = null)
    {
        return $this->repo()->find($id, $lockMode, $lockVersion);
    }

    public function findAll()
    {
        return $this->repo()->findAll();
    }

    public function findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
    {
        return $this->repo()->findBy($criteria, $orderBy, $limit, $offset);
    }

    public function findOneBy(array $criteria, array $orderBy = null)
    {
        return $this->repo()->findOneBy($criteria, $orderBy);
    }

    public function findByEmail(string $email) : ?User
    {
        return $this->repo()->findOneBy(['email' => $email]);
    }

    public function isEmailTaken(string $email) : bool
    {
        return (bool)$this->findByEmail($email);
    }

    public function save(User $user, bool $flush = true) : void
    {
        $this->em->persist($user);
        if ($flush) {
            $this->em->flush();
        }
    }

    public function findByResetToken(string $token) : ?User
    {
        return $this->repo()->findOneBy(['resetToken' => $token]);
    }

    public function flush() : void
    {
        $this->em->flush();
    }
}
