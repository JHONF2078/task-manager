<?php

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }
    // Métodos personalizados para Task

    /**
     * @param array $filters
     * @param int $page 1-based
     * @param int $limit
     * @param string|null $sort
     * @param string $direction
     * @return array [data=> Task[], total=>int]
     */
    public function search(array $filters, int $page = 1, int $limit = 20, ?string $sort = null, string $direction = 'asc'): array
    {
        $qb = $this->createQueryBuilder('t');

        // Soft delete filter (by default only active)
        $includeInactive = !empty($filters['includeInactive']);
        if (!$includeInactive) {
            $qb->andWhere('t.isActive = :active')->setParameter('active', true);
        }

        if (!empty($filters['q'])) {
            $qb->andWhere('t.title LIKE :q OR t.description LIKE :q')->setParameter('q', '%'.$filters['q'].'%');
        }
        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')->setParameter('status', $filters['status']);
        }
        if (!empty($filters['priority'])) {
            $qb->andWhere('t.priority = :priority')->setParameter('priority', $filters['priority']);
        }
        if (!empty($filters['assignedTo'])) {
            $qb->andWhere('t.assignedTo = :assigned')->setParameter('assigned', (int)$filters['assignedTo']);
        }
        if (!empty($filters['dueFrom'])) {
            $qb->andWhere('t.dueDate >= :dueFrom')->setParameter('dueFrom', $filters['dueFrom']);
        }
        if (!empty($filters['dueTo'])) {
            $qb->andWhere('t.dueDate <= :dueTo')->setParameter('dueTo', $filters['dueTo']);
        }
        if (!empty($filters['createdFrom'])) {
            $qb->andWhere('t.createdAt >= :cFrom')->setParameter('cFrom', $filters['createdFrom']);
        }
        if (!empty($filters['createdTo'])) {
            $qb->andWhere('t.createdAt <= :cTo')->setParameter('cTo', $filters['createdTo']);
        }
        if (!empty($filters['categories'])) {
            // Simple LIKE based filtering (fallback if JSON functions not available in portable DQL)
            foreach ($filters['categories'] as $idx => $cat) {
                $qb->andWhere($qb->expr()->like('t.categories', ':cat'.$idx))
                   ->setParameter('cat'.$idx, '%"'.addslashes($cat).'"%');
            }
        }

        if ($sort) {
            $allowed = ['title','status','priority','dueDate','createdAt','updatedAt'];
            if (in_array($sort, $allowed, true)) {
                $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
                $qb->addOrderBy('t.'.$sort, $direction);
            }
        } else {
            $qb->addOrderBy('t.createdAt', 'DESC');
        }

        $page = max(1, $page);
        $limit = max(1, min(100, $limit));
        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);

        $paginator = new Paginator($qb, true);
        $total = count($paginator);
        $data = iterator_to_array($paginator->getIterator());

        return [
            'data' => $data,
            'total' => $total,
        ];
    }

    /**
     * Devuelve el plan de ejecución (EXPLAIN) de la búsqueda con los filtros indicados.
     * No aplica paginación para el plan, sólo filtros y orden.
     * @param array $filters
     * @param string|null $sort
     * @param string $direction
     * @param bool $analyze
     * @return array{platform:string,sql:string,params:array,rows:array}
     */
    public function explainSearch(array $filters, ?string $sort = null, string $direction = 'asc', bool $analyze = false, ?int $timeoutMs = null): array
    {
        $qb = $this->createQueryBuilder('t');
        $includeInactive = !empty($filters['includeInactive']);
        if (!$includeInactive) { $qb->andWhere('t.isActive = :active')->setParameter('active', true); }
        if (!empty($filters['q'])) { $qb->andWhere('t.title LIKE :q OR t.description LIKE :q')->setParameter('q', '%'.$filters['q'].'%'); }
        if (!empty($filters['status'])) { $qb->andWhere('t.status = :status')->setParameter('status', $filters['status']); }
        if (!empty($filters['priority'])) { $qb->andWhere('t.priority = :priority')->setParameter('priority', $filters['priority']); }
        if (!empty($filters['assignedTo'])) { $qb->andWhere('t.assignedTo = :assigned')->setParameter('assigned', (int)$filters['assignedTo']); }
        if (!empty($filters['dueFrom'])) { $qb->andWhere('t.dueDate >= :dueFrom')->setParameter('dueFrom', $filters['dueFrom']); }
        if (!empty($filters['dueTo'])) { $qb->andWhere('t.dueDate <= :dueTo')->setParameter('dueTo', $filters['dueTo']); }
        if (!empty($filters['createdFrom'])) { $qb->andWhere('t.createdAt >= :cFrom')->setParameter('cFrom', $filters['createdFrom']); }
        if (!empty($filters['createdTo'])) { $qb->andWhere('t.createdAt <= :cTo')->setParameter('cTo', $filters['createdTo']); }
        if (!empty($filters['categories'])) {
            foreach ($filters['categories'] as $idx => $cat) {
                $qb->andWhere($qb->expr()->like('t.categories', ':cat'.$idx))
                   ->setParameter('cat'.$idx, '%"'.addslashes($cat).'"%');
            }
        }
        if ($sort) {
            $allowed = ['title','status','priority','dueDate','createdAt','updatedAt'];
            if (in_array($sort, $allowed, true)) {
                $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
                $qb->addOrderBy('t.'.$sort, $direction);
            }
        } else { $qb->addOrderBy('t.createdAt', 'DESC'); }

        $query = $qb->getQuery();
        $sql = $query->getSQL();
        $params = [];
        foreach ($query->getParameters() as $p) { $params[] = $p->getValue(); }

        $conn = $this->getEntityManager()->getConnection();
        $platform = $conn->getDatabasePlatform()->getName();

        $analyzeRequested = $analyze; // guardamos intención original
        $prefix = 'EXPLAIN ';
        if ($analyze) {
            if (stripos($platform, 'mysql') !== false) { $prefix = 'EXPLAIN ANALYZE '; }
            elseif (stripos($platform, 'postgres') !== false) { $prefix = 'EXPLAIN (ANALYZE, BUFFERS, VERBOSE) '; }
            elseif (stripos($platform, 'sqlite') !== false) { $prefix = 'EXPLAIN QUERY PLAN '; $analyze = false; }
        } else {
            if (stripos($platform, 'sqlite') !== false) { $prefix = 'EXPLAIN QUERY PLAN '; }
            elseif (stripos($platform,'postgres') !== false) { $prefix = 'EXPLAIN '; }
        }

        $timeoutApplied = false; $timeoutHit = false; $start = microtime(true); $rows = [];
        try {
            if ($timeoutMs && $timeoutMs > 0) {
                if (stripos($platform,'postgres') !== false) {
                    if (!$conn->isTransactionActive()) { $conn->beginTransaction(); $ownTx = true; } else { $ownTx = false; }
                    $conn->executeStatement('SET LOCAL statement_timeout = '.$timeoutMs);
                    $timeoutApplied = true;
                } elseif (stripos($platform,'mysql') !== false) {
                    $conn->executeStatement('SET SESSION MAX_EXECUTION_TIME='.$timeoutMs);
                    $timeoutApplied = true;
                }
            }
            $rows = $conn->fetchAllAssociative($prefix.$sql, $params);
        } catch (\Throwable $e) {
            $err = strtolower($e->getMessage());
            $rows = [['error' => $e->getMessage()]];
            if (str_contains($err, 'timeout') || str_contains($err,'execution time exceeded')) { $timeoutHit = true; }
            // Fallback: si ANALYZE no soportado en MySQL (<8) u otro error de sintaxis, reintentar sin ANALYZE
            if ($analyzeRequested && stripos($platform,'mysql') !== false && (str_contains($err,'analyze') || str_contains($err,'syntax'))) {
                try {
                    $analyze = false; // no se ejecutó realmente
                    $rows = $conn->fetchAllAssociative('EXPLAIN '.$sql, $params);
                    // Añadimos nota
                    $rows[] = ['notice' => 'ANALYZE no soportado, se mostró EXPLAIN estándar.'];
                } catch (\Throwable $e2) {
                    $rows[] = ['fallback_error' => $e2->getMessage()];
                }
            }
        } finally {
            if ($timeoutApplied) {
                try {
                    if (stripos($platform,'mysql') !== false) { $conn->executeStatement('SET SESSION MAX_EXECUTION_TIME=0'); }
                    if (isset($ownTx) && $ownTx) { $conn->commit(); }
                } catch (\Throwable $e) { /* noop */ }
            }
        }
        $elapsedMs = (int) ((microtime(true) - $start) * 1000);

        return [
            'platform' => $platform,
            'sql' => $sql,
            'params' => $params,
            'analyze' => $analyze, // indica si realmente se ejecutó ANALYZE
            'analyzeRequested' => $analyzeRequested,
            'timeoutMs' => $timeoutMs,
            'timeoutApplied' => $timeoutApplied,
            'timeoutHit' => $timeoutHit,
            'elapsedMs' => $elapsedMs,
            'rows' => $rows,
        ];
    }
}
