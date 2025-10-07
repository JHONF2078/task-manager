<?php

declare(strict_types=1);

namespace App\Service\Contract;

use App\Dto\Tasks\TaskFilterDto;
use Doctrine\ORM\QueryBuilder;

/**
 * Interfaz para el servicio de filtrado de tareas
 */
interface TaskFilterServiceInterface
{
    /**
     * Aplica los filtros a un QueryBuilder de tareas.
     *
     * @param QueryBuilder  $qb
     * @param TaskFilterDto $filters
     *
     * @return void
     */
    public function applyFilters(QueryBuilder $qb, TaskFilterDto $filters) : void;

    /**
     * Aplica el ordenamiento a un QueryBuilder de tareas.
     *
     * @param QueryBuilder $qb
     * @param string|null  $sort
     * @param string       $direction
     *
     * @return void
     */
    public function applySorting(QueryBuilder $qb, ?string $sort, string $direction = 'asc') : void;
}
