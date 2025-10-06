<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Entity\User;

/**
 * Interface para UserRepository
 * Solo declara métodos personalizados, no los heredados de ServiceEntityRepository
 */
interface UserRepositoryInterface
{
    /**
     * Métodos estándar de Doctrine
     */
    public function find(mixed $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;
    public function findAll(): array;
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * Cuenta los administradores activos.
     * @return int
     */
    public function countAdmins(): int;

    /**
     * Verifica si el usuario es el último administrador.
     * @param User $user
     * @return bool
     */
    public function isLastAdmin(User $user): bool;
}
