<?php

declare(strict_types=1);

namespace App\Mapper\Manual;

use App\Dto\Tasks\TaskResponseDto;
use App\Entity\Task;

class TaskResponseMapper
{
    /**
     * Mapea los datos del usuario asignado a un array
     */
    private static function mapAssignedUser($user): ?array
    {
        if (!$user) {
            return null;
        }

        return [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'name' => $user->getName(),
            'roles' => $user->getRoles(),
            'active' => $user->isActive()
        ];
    }

    public static function toDto(Task $task): TaskResponseDto
    {
        $dto              = new TaskResponseDto();
        $dto->id          = $task->getId();
        $dto->title       = $task->getTitle();
        $dto->description = $task->getDescription();
        $dto->status      = $task->getStatus();
        $dto->priority    = $task->getPriority();
        $dto->dueDate     = $task->getDueDate() ? $task->getDueDate()->format(\DateTimeInterface::ATOM) : null;
        $dto->assignedTo  = self::mapAssignedUser($task->getAssignedTo());
        $dto->categories  = $task->getCategories() ?? [];
        $dto->active      = $task->isActive();
        $dto->createdAt   = $task->getCreatedAt()->format('Y-m-d\TH:i:s.u\Z');
        $dto->updatedAt   = $task->getUpdatedAt()->format('Y-m-d\TH:i:s.u\Z');

        return $dto;
    }

    /**
     * Convierte la entidad Task directamente a un array
     */
    public static function toArray(Task $task): array
    {
        return [
            'id' => $task->getId(),
            'title' => $task->getTitle(),
            'description' => $task->getDescription(),
            'status' => $task->getStatus(),
            'priority' => $task->getPriority(),
            'dueDate' => $task->getDueDate() ? $task->getDueDate()->format(\DateTimeInterface::ATOM) : null,
            'assignedTo' => self::mapAssignedUser($task->getAssignedTo()),
            'categories' => $task->getCategories() ?? [],
            'active' => $task->isActive(),
            'createdAt' => $task->getCreatedAt()->format('Y-m-d\TH:i:s.u\Z'),
            'updatedAt' => $task->getUpdatedAt()->format('Y-m-d\TH:i:s.u\Z'),
        ];
    }
}
