<?php

declare(strict_types=1);

namespace App\Repository\Contract;

/**
 * Interface para RefreshTokenRepository
 * RefreshTokenRepository solo utiliza métodos heredados de ServiceEntityRepository
 * Esta interfaz sirve como marcador para inyección de dependencias
 */
interface RefreshTokenRepositoryInterface
{
    /**
     * Métodos estándar de Doctrine
     */
    public function find(mixed $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;
    public function findAll(): array;
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;
}
