<?php

declare(strict_types=1);

namespace App\Mapper;

use App\Dto\TaskCreateInput;
use App\Dto\TaskUpdateInput;
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
    public static function fromArrayToCreateInput(array $data) : TaskCreateInput
    {
        $dto              = new TaskCreateInput();
        $dto->title       = (string)($data['title'] ?? '');
        $dto->description = $data['description'] ?? null;
        $dto->status      = $data['status']      ?? null;
        $dto->priority    = $data['priority']    ?? null;
        $dto->dueDate     = self::parseDate($data['dueDate'] ?? null);
        $dto->assignedTo  = isset($data['assignedTo']) && $data['assignedTo'] !== '' ? (int)$data['assignedTo'] : null;
        $dto->categories  = $data['categories'] ?? null;
        return $dto;
    }

    //Convertir de array a UpdateInput
    public static function fromArrayToUpdateInput(array $data) : TaskUpdateInput
    {
        $dto = new TaskUpdateInput();
        foreach (['title','description','status','priority','categories'] as $k) {
            if (array_key_exists($k, $data)) {
                $dto->$k = $data[$k];
            }
        }
        if (array_key_exists('dueDate', $data)) {
            $dto->dueDate = self::parseDate($data['dueDate']);
        }
        if (array_key_exists('assignedTo', $data)) {
            $dto->assignedTo = $data['assignedTo'] !== null && $data['assignedTo'] !== '' ? (int)$data['assignedTo'] : null;
        }
        return $dto;
    }
}
