<?php

declare(strict_types=1);

namespace App\Service\Contract;

use App\Dto\TaskCreateInput;
use App\Dto\TaskUpdateInput;
use App\Entity\Task;

/**
 * Interfaz para el servicio de gestión de tareas
 */
interface TaskServiceInterface
{
    /**
     * Crea una tarea desde un array (legacy).
     * @param array $input
     * @return Task
     * @throws \App\Exception\ValidationException
     */
    public function create(array $input): Task;

    /**
     * Actualiza una tarea desde un array (legacy).
     * @param Task $task
     * @param array $input
     * @param bool $partial
     * @return Task
     * @throws \App\Exception\ValidationException
     */
    public function update(Task $task, array $input, bool $partial = false): Task;

    /**
     * Crea una tarea desde un DTO.
     * @param TaskCreateInput $dto
     * @return Task
     * @throws \App\Exception\ValidationException
     */
    public function createFromDto(TaskCreateInput $dto): Task;

    /**
     * Actualiza una tarea desde un DTO.
     * @param Task $task
     * @param TaskUpdateInput $dto
     * @param bool $partial
     * @return Task
     * @throws \App\Exception\ValidationException
     */
    public function updateFromDto(Task $task, TaskUpdateInput $dto, bool $partial = true): Task;

    /**
     * Crea una tarea desde una entidad mapeada.
     * @param Task $task
     * @return Task
     * @throws \App\Exception\ValidationException
     */
    public function createFromEntity(Task $task): Task;

    /**
     * Actualiza una tarea desde una entidad mapeada.
     * @param Task $task
     * @param Task $updatedEntity
     * @param bool $partial
     * @return Task
     * @throws \App\Exception\ValidationException
     */
    public function updateFromEntity(Task $task, Task $updatedEntity, bool $partial = true): Task;
}

