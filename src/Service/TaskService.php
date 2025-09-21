<?php declare(strict_types=1);

namespace App\Service;

use App\Dto\TaskCreateInput;
use App\Dto\TaskUpdateInput;
use App\Entity\Task;
use App\Exception\ValidationException;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TaskService
{
    public function __construct(
        private TaskRepository $taskRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private ValidatorInterface $validator
    ) {
    }

    public function create(array $input) : Task
    {   // Compatibilidad legacy: se conservará pero sin assertRequired()
        $task = new Task();
        $this->applyData($task, $input, false);
        $this->validateTask($task);
        $this->em->persist($task);
        $this->em->flush();
        return $task;
    }

    public function update(Task $task, array $input, bool $partial = false) : Task
    {
        $this->applyData($task, $input, $partial);
        $this->validateTask($task);
        $this->em->flush();
        return $task;
    }

    // Nuevos métodos con DTO
    public function createFromDto(TaskCreateInput $dto) : Task
    {
        $this->validateDto($dto);
        $task = new Task();
        $this->mapCreateDto($task, $dto);
        $this->validateTask($task);
        $this->em->persist($task);
        $this->em->flush();
        return $task;
    }

    public function updateFromDto(Task $task, TaskUpdateInput $dto, bool $partial = true) : Task
    {
        $this->validateDto($dto);
        $this->mapUpdateDto($task, $dto, $partial);
        $this->validateTask($task);
        $this->em->flush();
        return $task;
    }

    // Métodos restaurados
    public function softDelete(Task $task) : Task
    {
        if ($task->isActive()) {
            $task->deactivate();
            $this->em->flush();
        } return $task;
    }

    public function restore(Task $task) : Task
    {
        if (!$task->isActive()) {
            $task->activate();
            $this->em->flush();
        } return $task;
    }

    public function get(int $id, bool $includeInactive = false) : ?Task
    {
        $task = $this->taskRepository->find($id);
        if (!$task) {
            return null;
        } if (!$includeInactive && !$task->isActive()) {
            return null;
        } return $task;
    }

    private function mapCreateDto(Task $task, TaskCreateInput $dto) : void
    {
        $task->setTitle(trim($dto->title));
        if ($dto->description !== null) {
            $task->setDescription($dto->description);
        }
        if ($dto->status !== null) {
            $task->setStatus($dto->status);
        }
        if ($dto->priority !== null) {
            $task->setPriority($dto->priority);
        }
        if ($dto->dueDate !== null) {
            $this->applyDueDate($task, $dto->dueDate);
        }
        if ($dto->assignedTo !== null) {
            $this->applyAssigned($task, $dto->assignedTo);
        }
        if ($dto->categories !== null) {
            $this->applyCategories($task, $dto->categories);
        }
    }

    private function mapUpdateDto(Task $task, TaskUpdateInput $dto, bool $partial) : void
    {
        if ($dto->title !== null) {
            $task->setTitle(trim($dto->title));
        }
        if ($dto->description !== null) {
            $task->setDescription($dto->description);
        }
        if ($dto->status !== null) {
            $task->setStatus($dto->status);
        }
        if ($dto->priority !== null) {
            $task->setPriority($dto->priority);
        }
        if ($dto->dueDate !== null) {
            $this->applyDueDate($task, $dto->dueDate);
        }
        if ($dto->assignedTo !== null) {
            $this->applyAssigned($task, $dto->assignedTo);
        }
        if ($dto->categories !== null) {
            $this->applyCategories($task, $dto->categories);
        }
    }

    private function applyDueDate(Task $task, ?string $due) : void
    {
        if ($due) {
            try {
                $task->setDueDate(new \DateTimeImmutable($due));
            } catch (\Exception) {
                throw new \InvalidArgumentException('Formato de fecha inválido en dueDate');
            }
        } else {
            $task->setDueDate(null);
        }
    }

    private function applyAssigned(Task $task, ?int $userId) : void
    {
        if ($userId) {
            $user = $this->userRepository->find($userId);
            if (!$user) {
                throw new \InvalidArgumentException('Usuario asignado no encontrado');
            } $task->setAssignedTo($user);
        } else {
            $task->setAssignedTo(null);
        }
    }

    private function applyCategories(Task $task, array|string|null $categories) : void
    {
        if (is_string($categories)) {
            $categories = array_filter(array_map('trim', explode(',', $categories)));
        }
        if ($categories === null) {
            $categories = [];
        }
        if (!is_array($categories)) {
            throw new \InvalidArgumentException('categories debe ser array o string separada por comas');
        }
        $norm = [];
        foreach ($categories as $c) {
            $c = trim((string)$c);
            if ($c !== '') {
                $norm[] = $c;
            }
        }
        $task->setCategories($norm);
    }

    private function applyData(Task $task, array $input, bool $partial) : void
    {
        if (array_key_exists('title', $input)) {
            $task->setTitle(trim((string)$input['title']));
        }
        if (array_key_exists('description', $input)) {
            $task->setDescription($input['description'] !== null ? (string)$input['description'] : null);
        }
        if (array_key_exists('status', $input) && $input['status'] !== null) {
            $task->setStatus((string)$input['status']);
        }
        if (array_key_exists('priority', $input) && $input['priority'] !== null) {
            $task->setPriority((string)$input['priority']);
        }
        if (array_key_exists('dueDate', $input)) {
            $this->applyDueDate($task, $input['dueDate']);
        }
        if (array_key_exists('assignedTo', $input)) {
            $this->applyAssigned($task, $input['assignedTo'] ? (int)$input['assignedTo'] : null);
        }
        if (array_key_exists('categories', $input)) {
            $this->applyCategories($task, $input['categories']);
        }
    }

    private function validateTask(Task $task) : void
    {
        $violations = $this->validator->validate($task);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = ['field' => $v->getPropertyPath(),'message' => $v->getMessage()];
            } throw new ValidationException($errors);
        }
    }

    private function validateDto(object $dto) : void
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $v) {
                $errors[] = ['field' => $v->getPropertyPath(),'message' => $v->getMessage()];
            } throw new ValidationException($errors);
        }
    }
}
