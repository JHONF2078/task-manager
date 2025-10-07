<?php declare(strict_types=1);

namespace App\Controller;

use App\Controller\Traits\PaginateTrait;
use App\Dto\Tasks\TaskCreateRequest;
use App\Dto\Tasks\TaskFilterDto;
use App\Dto\Tasks\TaskResponseDto;
use App\Dto\Tasks\TaskUpdateRequest;
use App\Entity\Task;
use App\Exception\EntityNotFoundException;
use App\Exception\ValidationException;
use App\Helper\MapperHelper;
use App\Mapper\Manual\TaskResponseMapper;
use App\Repository\TaskRepository;
use App\Service\Contract\TaskServiceInterface;
use AutoMapperPlus\AutoMapperInterface;
use AutoMapperPlus\Exception\UnregisteredMappingException;
use DateTimeImmutable;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tasks')]
class TaskController extends AbstractController
{
    use PaginateTrait;

    public function __construct(
        private readonly TaskServiceInterface $taskService,
        private readonly TaskRepository $taskRepository,
        private readonly ValidatorInterface $validator,
        private readonly AutoMapperInterface $autoMapper,
        private readonly LoggerInterface $logger,
        private readonly MapperHelper $mapperHelper,
    ) {
    }

    /**
     * Lista las tareas con filtros y paginación
     *
     * @param Request $request Request con los parámetros de filtrado y paginación
     *
     * @throws UnregisteredMappingException
     *
     * @return JsonResponse
     */
    #[Route('', name: 'api_tasks_list', methods: ['GET'])]
    public function list(Request $request) : JsonResponse
    {
        try {
            // Convertir los query parameters a JSON
            $filterParams = json_encode([
                'q'               => $request->query->get('q'),
                'status'          => $request->query->get('status'),
                'priority'        => $request->query->get('priority'),
                'assignedTo'      => $request->query->get('assignedTo'),
                'dueFrom'         => $this->parseDateParam($request->query->get('dueFrom')),
                'dueTo'           => $this->parseDateParam($request->query->get('dueTo'), true),
                'createdFrom'     => $this->parseDateParam($request->query->get('createdFrom')),
                'createdTo'       => $this->parseDateParam($request->query->get('createdTo'), true),
                'categories'      => $request->query->get('categories'),
                'includeInactive' => $request->query->getBoolean('includeInactive')
            ]);

            // Usar MapperHelper para deserializar
            $filters = $this->mapperHelper->deserialize($filterParams, TaskFilterDto::class, 'json');

            // Validar los filtros
            $violations = $this->validator->validate($filters);
            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $v) {
                    $errors[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()];
                }
                throw new ValidationException($errors);
            }

            // Usamos el trait para obtener la paginación
            [$limit, $page] = $this->getPagination($request);

            $sort      = $request->query->get('sort');
            $direction = $request->query->get('direction', 'asc');

            $result = $this->taskRepository->search($filters, $page, $limit, $sort, $direction);

            // Usar estrategia manual con toArray para obtener el array directamente
            $dtos = $this->mapperHelper->mapCollection(
                $result['data'],
                TaskResponseDto::class,
                MapperHelper::STRATEGY_MANUAL_MAPPER,
                TaskResponseMapper::class,
                'toArray'
            );

            return $this->json([
                'data'  => $dtos,
                'total' => $result['total'],
                'page'  => $page,
                'limit' => $limit
            ]);
        } catch (ValidationException $e) {
            // Este catch ahora solo se activará para los errores de validación de filtros
            throw $e; // Relanzamos para que lo capture el ApiExceptionSubscriber
        } catch (\Exception $e) {
            $this->logger->error('Error al listar tareas: ' . $e->getMessage());
            // Relanzamos para que lo capture el ApiExceptionSubscriber
            throw $e;
        }
    }

    #[Route(path: '/{id}', name: 'api_tasks_get', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function getOne(int $id, Request $request) : JsonResponse
    {
        $includeInactive = $request->query->getBoolean('includeInactive');
        $task            = $this->taskService->get($id, $includeInactive);
        if (!$task) {
            throw new EntityNotFoundException('Tarea', $id);
        }

        $mappedTask = $this->mapperHelper->map(
            $task,
            TaskResponseDto::class,
            MapperHelper::STRATEGY_MANUAL_MAPPER,
            TaskResponseMapper::class,
            'toArray'
        );

        // Log temporal para depuración
        $this->logger->debug('Task response:', [
            'createdAt' => $task->getCreatedAt()->format('Y-m-d H:i:s'),
            'updatedAt' => $task->getUpdatedAt()->format('Y-m-d H:i:s'),
            'mapped'    => $mappedTask
        ]);

        return $this->json($mappedTask);
    }

    #[Route(path: '', name: 'api_tasks_create', methods: ['POST'])]
    public function create(Request $request) : JsonResponse
    {
        try {
            $dto = $this->mapperHelper->deserialize($request->getContent(), TaskCreateRequest::class, 'json');
            $this->throwIfViolations($this->validator->validate($dto));

            $task = $this->autoMapper->map($dto, Task::class);
            $task = $this->taskService->createFromEntity($task);

            return $this->json(
                $this->mapperHelper->map(
                    $task,
                    TaskResponseDto::class,
                    MapperHelper::STRATEGY_MANUAL_MAPPER,
                    TaskResponseMapper::class,
                    'toArray'
                ),
                201
            );
        } catch (UnregisteredMappingException $e) {
            return $this->json(['error' => 'Error de mapeo: ' . $e->getMessage(), 'violations' => []], 500);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage(), 'violations' => []], 400);
        }
    }

    #[Route(path: '/{id}', name: 'api_tasks_replace', requirements: ['id' => '\\d+'], methods: ['PUT'])]
    public function replace(int $id, Request $request) : JsonResponse
    {
        $task = $this->taskService->get($id, true);
        if (!$task) {
            return $this->json(['error' => 'Tarea no encontrada'], 404);
        }

        try {
            $dto = $this->mapperHelper->deserialize($request->getContent(), TaskCreateRequest::class, 'json');
            $this->throwIfViolations($this->validator->validate($dto));

            $updatedEntity = $this->autoMapper->map($dto, Task::class);
            $updated       = $this->taskService->updateFromEntity($task, $updatedEntity, false);

            return $this->json(
                $this->mapperHelper->map(
                    $updated,
                    TaskResponseDto::class,
                    MapperHelper::STRATEGY_MANUAL_MAPPER,
                    TaskResponseMapper::class,
                    'toArray'
                )
            );
        } catch (UnregisteredMappingException $e) {
            return $this->json([
                'error'      => 'Error de mapeo: ' . $e->getMessage(),
                'violations' => []
            ], 500);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage(), 'violations' => []], 400);
        }
    }

    #[Route(path: '/{id}', name: 'api_tasks_patch', requirements: ['id' => '\\d+'], methods: ['PATCH'])]
    public function patch(int $id, Request $request) : JsonResponse
    {
        $task = $this->taskService->get($id, true);
        if (!$task) {
            return $this->json(['error' => 'Tarea no encontrada'], 404);
        }

        try {
            $dto = $this->mapperHelper->deserialize($request->getContent(), TaskUpdateRequest::class, 'json');
            $this->throwIfViolations($this->validator->validate($dto));

            $updatedEntity = $this->autoMapper->mapToObject($dto, $task);
            $updated       = $this->taskService->updateFromEntity($task, $updatedEntity);

            return $this->json(
                $this->mapperHelper->map(
                    $updated,
                    TaskResponseDto::class,
                    MapperHelper::STRATEGY_MANUAL_MAPPER,
                    TaskResponseMapper::class,
                    'toArray'
                )
            );
        } catch (UnregisteredMappingException $e) {
            return $this->json([
                'error'      => 'Error de mapeo: ' . $e->getMessage(),
                'violations' => []
            ], 500);
        } catch (InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage(), 'violations' => []], 400);
        }
    }

    #[Route(
        path: '/{id}',
        name: 'api_tasks_delete',
        requirements: ['id' => '\\d+'],
        methods: ['DELETE']
    )]
    public function delete(int $id) : JsonResponse
    {
        $task = $this->taskService->get($id, true);
        if (!$task) {
            return $this->json(['error' => 'Tarea no encontrada'], 404);
        }
        $this->taskService->softDelete($task);
        return $this->json(
            $this->mapperHelper->map(
                $task,
                TaskResponseDto::class,
                MapperHelper::STRATEGY_MANUAL_MAPPER,
                TaskResponseMapper::class,
                'toArray'
            )
        );
    }

    #[Route(
        path: '/{id}/restore',
        name: 'api_tasks_restore',
        requirements: ['id' => '\\d+'],
        methods: ['PATCH']
    )]
    public function restore(int $id) : JsonResponse
    {
        $task = $this->taskService->get($id, true);
        if (!$task) {
            return $this->json(['error' => 'Tarea no encontrada'], 404);
        }
        $this->taskService->restore($task);
        return $this->json(
            $this->mapperHelper->map(
                $task,
                TaskResponseDto::class,
                MapperHelper::STRATEGY_MANUAL_MAPPER,
                TaskResponseMapper::class,
                'toArray'
            )
        );
    }

    #[Route(
        path: '/explain',
        name: 'api_tasks_explain',
        methods: ['GET']
    )]
    public function explain(Request $request) : JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $filters      = $this->buildTaskFilters($request);
        $sort         = $request->query->get('sort');
        $direction    = $request->query->get('direction', 'asc');
        $analyze      = $request->query->getBoolean('analyze');
        $timeoutMsRaw = $request->query->get('timeoutMs');
        $timeoutMs    = is_numeric($timeoutMsRaw) ? max(1, (int)$timeoutMsRaw) : null;
        if ($timeoutMs !== null && $timeoutMs > 30000) {
            $timeoutMs = 30000;
        }
        $plan            = $this->taskRepository->explainSearch($filters, $sort, $direction, $analyze, $timeoutMs);
        $recommendations = $this->buildRecommendations($filters, $plan, $sort);
        return $this->json([
            'filters'   => array_filter($filters, fn ($v) => $v !== null && $v !== ''),
            'sort'      => $sort,
            'direction' => $direction,
            'analyze'   => $plan['analyze'],
            'timeout'   => [
                'requestedMs' => $timeoutMs,
                'applied'     => $plan['timeoutApplied'],
                'hit'         => $plan['timeoutHit'],
                'elapsedMs'   => $plan['elapsedMs'],
            ],
            'explain' => [
                'platform' => $plan['platform'],
                'sql'      => $plan['sql'],
                'params'   => $plan['params'],
                'rows'     => $plan['rows'],
            ],
            'recommendations' => $recommendations,
        ]);
    }

    private function buildRecommendations(array $filters, array $plan, ?string $sort) : array
    {
        $recs           = [];
        $platform       = $plan['platform'] ?? '';
        $sql            = $plan['sql']      ?? '';
        $rows           = $plan['rows']     ?? [];
        $scanIndicators = [];
        foreach ($rows as $r) {
            $joined = strtolower(implode(' ', array_map(fn ($v) => is_scalar($v) ? (string)$v : '', $r)));
            if (str_contains($joined, 'seq scan') || str_contains($joined, 'table scan') || str_contains($joined, 'type all')) {
                $scanIndicators[] = $joined;
            }
        }

        $filteredColumns = [];
        foreach (['status','priority','assignedTo','dueFrom','dueTo','q','categories'] as $k) {
            if (!empty($filters[$k])) {
                $filteredColumns[] = $k;
            }
        }

        if ($scanIndicators && $filteredColumns) {
            $recs[] = 'Se detectó un escaneo completo con filtros en: ' . implode(', ', $filteredColumns) . '. Considera índices (ej: (status), (priority), (assigned_to), (due_date)).';
        }

        if (!empty($filters['q']) && str_contains($sql, 'LIKE') && str_contains($sql, '%' . $filters['q'] . '%')) {
            if (stripos($platform, 'postgres') !== false) {
                $recs[] = 'Uso de LIKE con comodines en medio. Considera extensión pg_trgm (GIN) para acelerar búsquedas de texto.';
            } elseif (stripos($platform, 'mysql') !== false) {
                $recs[] = 'Uso de LIKE con comodines en medio. Un índice BTREE no ayudará; evalúa FULLTEXT si aplica (innoDB >=5.6).';
            }
        }

        if ((!empty($filters['dueFrom']) || !empty($filters['dueTo'])) && !str_contains(strtolower($sql), 'due_date')) {
            // improbable, pero guardado
            $recs[] = 'Se filtra por rango de fechas; asegura un índice en due_date.';
        }

        if (!empty($filters['categories'])) {
            $recs[] = 'Filtro por categories usando LIKE. Considera normalizar a tabla relacional (task_category) o JSON con índices especializados.';
        }

        if (in_array($sort, ['createdAt','updatedAt','dueDate']) && !str_contains(strtolower($sql), $sort)) {
            $recs[] = 'Orden por ' . $sort . ' podría necesitar índice para evitar sort costoso.';
        }

        if ($plan['timeoutHit'] ?? false) {
            $recs[] = 'La consulta alcanzó el timeout. Revisa índices y reduce filtros amplios.';
        } elseif (($plan['elapsedMs'] ?? 0) > 1000) {
            $recs[] = 'La consulta tardó más de 1s; analiza índices compuestos sobre columnas filtradas frecuentemente.';
        }

        if (!$recs) {
            $recs[] = 'Sin recomendaciones críticas: el plan no muestra escaneos completos significativos.';
        }
        return $recs;
    }

    private function parseDateParam(?string $value, bool $endOfDay = false) : ?DateTimeImmutable
    {
        if (!$value) {
            return null;
        }
        $value = trim($value);
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $value .= $endOfDay ? ' 23:59:59' : ' 00:00:00';
        }
        try {
            return new DateTimeImmutable($value);
        } catch (Exception) {
            return null; // ignorar filtros inválidos
        }
    }

    private function throwIfViolations($violations) : void
    {
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = ['field' => $v->getPropertyPath(), 'message' => $v->getMessage()];
            }
            throw new ValidationException($errors);
        }
    }

    private function buildTaskFilters(Request $request) : array
    {
        $dueFromRaw = $request->query->get('dueFrom');
        $dueToRaw   = $request->query->get('dueTo');
        $dueFrom    = $this->parseDateParam($dueFromRaw);
        $dueTo      = $this->parseDateParam($dueToRaw, true);
        if ($dueFrom && $dueTo && $dueFrom > $dueTo) {
            [$dueFrom, $dueTo] = [$dueTo, $dueFrom];
        }
        $filters = [
            'q'               => $request->query->get('q'),
            'status'          => $request->query->get('status'),
            'priority'        => $request->query->get('priority'),
            'assignedTo'      => $request->query->get('assignedTo'),
            'dueFrom'         => $dueFrom,
            'dueTo'           => $dueTo,
            'includeInactive' => $request->query->getBoolean('includeInactive'),
        ];
        $categoriesParam = $request->query->get('categories');
        if ($categoriesParam) {
            $filters['categories'] = array_filter(array_map('trim', explode(',', $categoriesParam)));
        }
        return $filters;
    }
}
