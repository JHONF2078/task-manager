<?php

declare(strict_types=1);

namespace App\Mapper;

use App\Dto\Tasks\TaskCreateRequest;
use App\Dto\Tasks\TaskUpdateRequest;
use DateTimeImmutable;

class TaskInputMapper
{
    private static function parseDate(?string $date) : ?DateTimeImmutable
    {
        if (empty($date)) {
            return null;
        }

        // Asegurarse de que la fecha esté en formato YYYY-MM-DD
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return new DateTimeImmutable($date);
        }

        return null;
    }

    //Convertir de array a CreateInput
    public static function fromArrayToCreateInput(array $data) : TaskCreateRequest
    {
        $dto              = new TaskCreateRequest();
        $dto->title       = $data['title']       ?? '';
        $dto->description = $data['description'] ?? null;
        $dto->status      = $data['status']      ?? null;
        $dto->priority    = $data['priority']    ?? null;
        $dto->dueDate     = $data['dueDate']     ?? null;
        $dto->assignedTo  = isset($data['assignedTo']) ? (int)$data['assignedTo'] : null;
        $dto->categories  = $data['categories']  ?? null;
        return $dto;
    }

    //Convertir de array a UpdateInput
    public static function fromArrayToUpdateInput(array $data) : TaskUpdateRequest
    {
        $dto = new TaskUpdateRequest();
        if (isset($data['title'])) {
            $dto->title = $data['title'];
        }
        if (isset($data['description'])) {
            $dto->description = $data['description'];
        }
        if (isset($data['status'])) {
            $dto->status = $data['status'];
        }
        if (isset($data['priority'])) {
            $dto->priority = $data['priority'];
        }
        if (isset($data['dueDate'])) {
            $dto->dueDate = $data['dueDate'];
        }
        if (isset($data['assignedTo'])) {
            $dto->assignedTo = (int)$data['assignedTo'];
        }
        if (isset($data['categories'])) {
            $dto->categories = $data['categories'];
        }
        return $dto;
    }
}
