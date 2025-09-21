<?php
namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;

class TaskCreateInput
{
    #[Assert\NotBlank(message: 'El título es obligatorio')]
    #[Assert\Length(max:255, maxMessage: 'El título no puede exceder {{ limit }} caracteres')]
    public string $title;

    #[Assert\Length(max:5000, maxMessage: 'La descripción es demasiado larga')]
    public ?string $description = null;

    #[Assert\Choice(callback: ['App\\Entity\\Task','validStatuses'], message: 'Estado inválido')]
    public ?string $status = null;

    #[Assert\Choice(callback: ['App\\Entity\\Task','validPriorities'], message: 'Prioridad inválida')]
    public ?string $priority = null;

    // ISO8601 o fecha Y-m-d opcional
    public ?string $dueDate = null;

    // id numérico usuario asignado
    #[Assert\Positive(message: 'assignedTo debe ser id positivo')]
    public ?int $assignedTo = null;

    // Puede venir array de strings o string separada por comas (lo normalizaremos luego)
    public array|string|null $categories = null;
}

