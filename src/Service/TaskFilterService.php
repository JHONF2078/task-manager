<?php declare(strict_types=1);

namespace App\Service;

use App\Dto\TaskFilterDto;
use App\Service\Contract\TaskFilterServiceInterface;
use Doctrine\ORM\QueryBuilder;

class TaskFilterService implements TaskFilterServiceInterface
{
    /**
     * Aplica los filtros a un QueryBuilder de tareas
     */
    public function applyFilters(QueryBuilder $qb, TaskFilterDto $filters): void
    {
        if (!$filters->includeInactive) {
            $qb->andWhere('t.isActive = :active')
               ->setParameter('active', true);
        }

        if ($filters->searchTerm) {
            $qb->andWhere('(t.title LIKE :searchTerm OR t.description LIKE :searchTerm)')
               ->setParameter('searchTerm', '%' . $filters->searchTerm . '%');
        }

        if ($filters->status) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters->status);
        }

        if ($filters->priority) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $filters->priority);
        }

        if ($filters->assignedTo) {
            $qb->andWhere('t.assignedTo = :assigned')
               ->setParameter('assigned', $filters->assignedTo);
        }

        if ($filters->dueFrom) {
            $qb->andWhere('t.dueDate >= :dueFrom')
               ->setParameter('dueFrom', $filters->dueFrom);
        }

        if ($filters->dueTo) {
            $qb->andWhere('t.dueDate <= :dueTo')
               ->setParameter('dueTo', $filters->dueTo);
        }

        if ($filters->createdFrom) {
            $qb->andWhere('t.createdAt >= :cFrom')
               ->setParameter('cFrom', $filters->createdFrom);
        }

        if ($filters->createdTo) {
            $qb->andWhere('t.createdAt <= :cTo')
               ->setParameter('cTo', $filters->createdTo);
        }

        if ($filters->categories) {
            foreach ($filters->categories as $idx => $cat) {
                $paramName = 'cat' . $idx;
                $qb->andWhere($qb->expr()->like('t.categories', ':' . $paramName))
                   ->setParameter($paramName, '%"' . addslashes($cat) . '"%');
            }
        }
    }

    /**
     * Aplica el ordenamiento a un QueryBuilder de tareas
     */
    public function applySorting(QueryBuilder $qb, ?string $sort, string $direction = 'asc'): void
    {
        $allowed = ['title', 'status', 'priority', 'dueDate', 'createdAt', 'updatedAt'];

        if ($sort && in_array($sort, $allowed, true)) {
            $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
            $qb->addOrderBy('t.' . $sort, $direction);
        } else {
            $qb->addOrderBy('t.createdAt', 'DESC');
        }
    }
}
