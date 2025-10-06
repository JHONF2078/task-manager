<?php

declare(strict_types=1);

namespace App\Mapper\Manual;

use App\Dto\TaskResponseDto;
use App\Entity\Task;

class TaskResponseMapper
{
    public static function toDto(Task $task) : TaskResponseDto
    {
        $dto              = new TaskResponseDto();
        $dto->id          = $task->getId();
        $dto->title       = $task->getTitle();
        $dto->description = $task->getDescription();
        $dto->status      = $task->getStatus();
        $dto->priority    = $task->getPriority();
        $dto->dueDate     = $task->getDueDate() ? $task->getDueDate()->format(\DateTimeInterface::ATOM) : null;

        // Mapear el usuario asignado como un array con toda la información necesaria
        $assignedTo = $task->getAssignedTo();
        $dto->assignedTo = $assignedTo ? [
            'id' => $assignedTo->getId(),
            'email' => $assignedTo->getEmail(),
            'name' => $assignedTo->getName(),
            'roles' => $assignedTo->getRoles(),
            'active' => $assignedTo->isActive()
        ] : null;

        $dto->categories  = $task->getCategories() ?? [];
        $dto->active      = $task->isActive();

        // Agregar las fechas de creación y actualización
        $dto->createdAt   = $task->getCreatedAt()->format('Y-m-d H:i:s');
        $dto->updatedAt   = $task->getUpdatedAt()->format('Y-m-d H:i:s');

        return $dto;
    }
}
