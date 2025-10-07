<?php

declare(strict_types=1);

namespace App\Repository\Contract;

use App\Dto\Tasks\TaskFilterDto;
use App\Entity\Task;

/**
 * Interface para TaskRepository
 * Solo declara métodos personalizados, no los heredados de ServiceEntityRepository
 */
interface TaskRepositoryInterface
{
    /**
     * Métodos estándar de Doctrine
     */
    public function find(mixed $id, \Doctrine\DBAL\LockMode|int|null $lockMode = null, ?int $lockVersion = null): ?object;
    public function findAll(): array;
    public function findBy(array $criteria, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;
    public function findOneBy(array $criteria, ?array $orderBy = null): ?object;

    /**
     * Busca tareas aplicando filtros y paginación.
     * @param TaskFilterDto $filters
     * @param int $page
     * @param int $limit
     * @param string|null $sort
     * @param string $direction
     * @return array{data: Task[], total: int}
     */
    public function search(
        TaskFilterDto $filters,
        int $page = 1,
        int $limit = 20,
        ?string $sort = null,
        string $direction = 'asc'
    ): array;
}
