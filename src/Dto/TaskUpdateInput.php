<?php
namespace App\Dto;
use Symfony\Component\Validator\Constraints as Assert;

class TaskUpdateInput
{
    // Todos opcionales en PUT/PATCH; constraints solo validan si hay valor
    #[Assert\Length(max:255, maxMessage: 'El título no puede exceder {{ limit }} caracteres')]
    public ?string $title = null;

    #[Assert\Length(max:5000, maxMessage: 'La descripción es demasiado larga')]
    public ?string $description = null;

    #[Assert\Choice(callback: ['App\\Entity\\Task','validStatuses'], message: 'Estado inválido')]
    public ?string $status = null;

    #[Assert\Choice(callback: ['App\\Entity\\Task','validPriorities'], message: 'Prioridad inválida')]
    public ?string $priority = null;

    // Formato esperado YYYY-MM-DD (se recorta en el front) o null
    public ?string $dueDate = null;

    #[Assert\Positive(message: 'assignedTo debe ser id positivo')]
    public ?int $assignedTo = null;

    // Array de strings (tags). Puede llegar null
    public array|string|null $categories = null;
}

