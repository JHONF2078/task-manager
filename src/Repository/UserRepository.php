<?php declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Repository\Contract\UserRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository implements UserRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // Métodos personalizados para User

    public function countAdmins() : int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->andWhere('u.isActive = :active')
            ->setParameter('role', '%ROLE_ADMIN%')
            ->setParameter('active', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function isLastAdmin(User $user) : bool
    {
        if (!in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return false; // No es admin actualmente
        }
        return $this->countAdmins() === 1;
    }
}
