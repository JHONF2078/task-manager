<?php

namespace App\Controller;

use App\Entity\Task;
use App\Service\TaskService;
use App\Repository\TaskRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Exception\EntityNotFoundException;
use App\Exception\ValidationException;
use App\Dto\TaskCreateInput;
use App\Dto\TaskUpdateInput;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/tasks')]
class TaskController extends AbstractController
{
    public function __construct(
        private TaskService $taskService,
        private TaskRepository $taskRepository,
        private ValidatorInterface $validator,
    ) {}

    #[Route('', name: 'api_tasks_list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        // Parseo robusto de fechas de vencimiento
        $dueFromRaw = $request->query->get('dueFrom');
        $dueToRaw = $request->query->get('dueTo');
        $dueFrom = $this->parseDateParam($dueFromRaw, false);
        $dueTo = $this->parseDateParam($dueToRaw, true);
        if ($dueFrom && $dueTo && $dueFrom > $dueTo) { // swap si invertido
            [$dueFrom, $dueTo] = [$dueTo, $dueFrom];
        }

        $filters = [
            'q' => $request->query->get('q'),
            'status' => $request->query->get('status'),
            'priority' => $request->query->get('priority'),
            'assignedTo' => $request->query->get('assignedTo'),
            'dueFrom' => $dueFrom,
            'dueTo' => $dueTo,
            'includeInactive' => $request->query->getBoolean('includeInactive', false),
        ];
        $categoriesParam = $request->query->get('categories');
        if ($categoriesParam) {
            $filters['categories'] = array_filter(array_map('trim', explode(',', $categoriesParam)));
        }
        $page = max(1, (int)$request->query->get('page', 1));
        $limit = max(1, min(100, (int)$request->query->get('limit', 20)));
        $sort = $request->query->get('sort');
        $direction = $request->query->get('direction', 'asc');

        $result = $this->taskRepository->search($filters, $page, $limit, $sort, $direction);
        $data = array_map(fn(Task $t) => $this->serializeTask($t), $result['data']);
        $total = $result['total'];
        $pages = (int)ceil($total / $limit);

        return $this->json([
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => $pages,
            ],
            'data' => $data,
        ]);
    }

    #[Route('/{id}', name: 'api_tasks_get', methods: ['GET'], requirements: ['id' => '\\d+'])]
    public function getOne(int $id, Request $request): JsonResponse
    {
        $includeInactive = $request->query->getBoolean('includeInactive', false);
        $task = $this->taskService->get($id, $includeInactive);
        if (!$task) {
            throw new EntityNotFoundException('Tarea', $id);
        }
        return $this->json($this->serializeTask($task));
    }

    #[Route('', name: 'api_tasks_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {   $data = json_decode($request->getContent(), true) ?? [];
        $dto = new TaskCreateInput();
        $dto->title = (string)($data['title'] ?? '');
        $dto->description = $data['description'] ?? null;
        $dto->status = $data['status'] ?? null;
        $dto->priority = $data['priority'] ?? null;
        $dto->dueDate = $data['dueDate'] ?? null;
        $dto->assignedTo = isset($data['assignedTo']) && $data['assignedTo'] !== '' ? (int)$data['assignedTo'] : null;
        $dto->categories = $data['categories'] ?? null;
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) { $errors=[]; foreach($violations as $v){ $errors[]=['field'=>$v->getPropertyPath(),'message'=>$v->getMessage()]; } throw new ValidationException($errors); }
        try {
            $task = $this->taskService->createFromDto($dto);
            return $this->json($this->serializeTask($task), 201);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage(), 'violations' => []], 400);
        }
    }

    #[Route('/{id}', name: 'api_tasks_replace', methods: ['PUT'], requirements: ['id' => '\\d+'])]
    public function replace(int $id, Request $request): JsonResponse
    {   $task = $this->taskService->get($id, true); if (!$task) { return $this->json(['error' => 'Tarea no encontrada'], 404); }
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new TaskCreateInput(); // Para PUT requerimos título
        $dto->title = (string)($data['title'] ?? '');
        $dto->description = $data['description'] ?? null;
        $dto->status = $data['status'] ?? null;
        $dto->priority = $data['priority'] ?? null;
        $dto->dueDate = $data['dueDate'] ?? null;
        $dto->assignedTo = isset($data['assignedTo']) && $data['assignedTo'] !== '' ? (int)$data['assignedTo'] : null;
        $dto->categories = $data['categories'] ?? null;
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) { $errors=[]; foreach($violations as $v){ $errors[]=['field'=>$v->getPropertyPath(),'message'=>$v->getMessage()]; } throw new ValidationException($errors); }
        try { $updated = $this->taskService->updateFromDto($task, (function(TaskCreateInput $c){ $u = new TaskUpdateInput(); $u->title=$c->title; $u->description=$c->description; $u->status=$c->status; $u->priority=$c->priority; $u->dueDate=$c->dueDate; $u->assignedTo=$c->assignedTo; $u->categories=$c->categories; return $u; })($dto), false); return $this->json($this->serializeTask($updated)); }
        catch (\InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage(),'violations'=>[]],400); }
    }

    #[Route('/{id}', name: 'api_tasks_patch', methods: ['PATCH'], requirements: ['id' => '\\d+'])]
    public function patch(int $id, Request $request): JsonResponse
    {   $task = $this->taskService->get($id, true); if (!$task) { return $this->json(['error'=>'Tarea no encontrada'],404); }
        $data = json_decode($request->getContent(), true) ?? [];
        $dto = new TaskUpdateInput();
        foreach (['title','description','status','priority','dueDate','categories'] as $k) { if (array_key_exists($k,$data)) { $dto->$k = $data[$k]; } }
        if (array_key_exists('assignedTo',$data)) { $dto->assignedTo = $data['assignedTo'] !== null && $data['assignedTo'] !== '' ? (int)$data['assignedTo'] : null; }
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) { $errors=[]; foreach($violations as $v){ $errors[]=['field'=>$v->getPropertyPath(),'message'=>$v->getMessage()]; } throw new ValidationException($errors); }
        try { $updated = $this->taskService->updateFromDto($task, $dto, true); return $this->json($this->serializeTask($updated)); }
        catch (\InvalidArgumentException $e) { return $this->json(['error'=>$e->getMessage(),'violations'=>[]],400); }
    }

    #[Route('/{id}', name: 'api_tasks_delete', methods: ['DELETE'], requirements: ['id' => '\\d+'])]
    public function delete(int $id): JsonResponse
    {
        $task = $this->taskService->get($id, true);
        if (!$task) { return $this->json(['error' => 'Tarea no encontrada'], 404); }
        $this->taskService->softDelete($task);
        return $this->json($this->serializeTask($task));
    }

    #[Route('/{id}/restore', name: 'api_tasks_restore', methods: ['PATCH'], requirements: ['id' => '\\d+'])]
    public function restore(int $id): JsonResponse
    {
        $task = $this->taskService->get($id, true);
        if (!$task) { return $this->json(['error' => 'Tarea no encontrada'], 404); }
        $this->taskService->restore($task);
        return $this->json($this->serializeTask($task));
    }

    #[Route('/explain', name: 'api_tasks_explain', methods: ['GET'])]
    public function explain(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $dueFrom = $this->parseDateParam($request->query->get('dueFrom'), false);
        $dueTo = $this->parseDateParam($request->query->get('dueTo'), true);
        if ($dueFrom && $dueTo && $dueFrom > $dueTo) { [$dueFrom, $dueTo] = [$dueTo, $dueFrom]; }

        $filters = [
            'q' => $request->query->get('q'),
            'status' => $request->query->get('status'),
            'priority' => $request->query->get('priority'),
            'assignedTo' => $request->query->get('assignedTo'),
            'dueFrom' => $dueFrom,
            'dueTo' => $dueTo,
            'includeInactive' => $request->query->getBoolean('includeInactive', false),
        ];
        $categoriesParam = $request->query->get('categories');
        if ($categoriesParam) { $filters['categories'] = array_filter(array_map('trim', explode(',', $categoriesParam))); }

        $sort = $request->query->get('sort');
        $direction = $request->query->get('direction', 'asc');
        $analyze = $request->query->getBoolean('analyze', false);
        $timeoutMsRaw = $request->query->get('timeoutMs');
        $timeoutMs = is_numeric($timeoutMsRaw) ? max(1, (int)$timeoutMsRaw) : null;
        // Limitar a 30s máximo para evitar abusos
        if ($timeoutMs !== null && $timeoutMs > 30000) { $timeoutMs = 30000; }

        $plan = $this->taskRepository->explainSearch($filters, $sort, $direction, $analyze, $timeoutMs);

        $recommendations = $this->buildRecommendations($filters, $plan, $sort);

        return $this->json([
            'filters' => array_filter($filters, fn($v) => $v !== null && $v !== ''),
            'sort' => $sort,
            'direction' => $direction,
            'analyze' => $plan['analyze'],
            'timeout' => [
                'requestedMs' => $timeoutMs,
                'applied' => $plan['timeoutApplied'],
                'hit' => $plan['timeoutHit'],
                'elapsedMs' => $plan['elapsedMs'],
            ],
            'explain' => [
                'platform' => $plan['platform'],
                'sql' => $plan['sql'],
                'params' => $plan['params'],
                'rows' => $plan['rows'],
            ],
            'recommendations' => $recommendations,
        ]);
    }

    private function buildRecommendations(array $filters, array $plan, ?string $sort): array
    {
        $recs = [];
        $platform = $plan['platform'] ?? '';
        $sql = $plan['sql'] ?? '';
        $rows = $plan['rows'] ?? [];
        $scanIndicators = [];
        foreach ($rows as $r) {
            $joined = strtolower(implode(' ', array_map(fn($v)=> is_scalar($v)? (string)$v : '', $r)));
            if (str_contains($joined, 'seq scan') || str_contains($joined, 'table scan') || str_contains($joined, 'type all')) {
                $scanIndicators[] = $joined;
            }
        }

        $filteredColumns = [];
        foreach (['status','priority','assignedTo','dueFrom','dueTo','q','categories'] as $k) {
            if (!empty($filters[$k])) { $filteredColumns[] = $k; }
        }

        if ($scanIndicators && $filteredColumns) {
            $recs[] = 'Se detectó un escaneo completo con filtros en: '.implode(', ', $filteredColumns).'. Considera índices (ej: (status), (priority), (assigned_to), (due_date)).';
        }

        if (!empty($filters['q']) && str_contains($sql, 'LIKE') && str_contains($sql, '%'.$filters['q'].'%')) {
            if (stripos($platform,'postgres') !== false) {
                $recs[] = 'Uso de LIKE con comodines en medio. Considera extensión pg_trgm (GIN) para acelerar búsquedas de texto.';
            } elseif (stripos($platform,'mysql') !== false) {
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

        if ($sort && in_array($sort, ['createdAt','updatedAt','dueDate']) && !str_contains(strtolower($sql), $sort)) {
            $recs[] = 'Orden por '.$sort.' podría necesitar índice para evitar sort costoso.';
        }

        if ($plan['timeoutHit'] ?? false) {
            $recs[] = 'La consulta alcanzó el timeout. Revisa índices y reduce filtros amplios.';
        } elseif (($plan['elapsedMs'] ?? 0) > 1000) {
            $recs[] = 'La consulta tardó más de 1s; analiza índices compuestos sobre columnas filtradas frecuentemente.';
        }

        if (!$recs) { $recs[] = 'Sin recomendaciones críticas: el plan no muestra escaneos completos significativos.'; }
        return $recs;
    }

    private function serializeTask(Task $t): array
    {
        return [
            'id' => $t->getId(),
            'title' => $t->getTitle(),
            'description' => $t->getDescription(),
            'status' => $t->getStatus(),
            'priority' => $t->getPriority(),
            'dueDate' => $t->getDueDate()?->format(DATE_ISO8601),
            'categories' => $t->getCategories(),
            'assignedTo' => $t->getAssignedTo() ? [
                'id' => $t->getAssignedTo()->getId(),
                'email' => $t->getAssignedTo()->getEmail(),
            ] : null,
            'createdAt' => $t->getCreatedAt()->format(DATE_ISO8601),
            'updatedAt' => $t->getUpdatedAt()->format(DATE_ISO8601),
            'active' => $t->isActive(),
            'deletedAt' => $t->getDeletedAt()?->format(DATE_ISO8601),
        ];
    }

    private function parseDateParam(?string $value, bool $endOfDay = false): ?\DateTimeImmutable
    {
        if (!$value) { return null; }
        $value = trim($value);
        // Si viene sólo YYYY-MM-DD agregamos hora
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $value .= $endOfDay ? ' 23:59:59' : ' 00:00:00';
        }
        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $e) {
            return null; // ignorar filtros inválidos
        }
    }
}
