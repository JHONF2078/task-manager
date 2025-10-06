<?php

declare(strict_types=1);

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\Groups;

class TaskResponseDto
{
    #[Groups(['task:read'])]
    public int $id;

    #[Groups(['task:read'])]
    public string $title;

    #[Groups(['task:read'])]
    public ?string $description = null;

    #[Groups(['task:read'])]
    public string $status;

    #[Groups(['task:read'])]
    public string $priority;

    #[Groups(['task:read'])]
    public ?string $dueDate = null;

    #[Groups(['task:read'])]
    public ?array $assignedTo = null;

    #[Groups(['task:read'])]
    public array $categories = [];

    #[Groups(['task:read'])]
    public bool $active = true;

    #[Groups(['task:read'])]
    public ?string $createdAt = null;

    #[Groups(['task:read'])]
    public ?string $updatedAt = null;
}
