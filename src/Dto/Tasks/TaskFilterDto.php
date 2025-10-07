<?php declare(strict_types=1);

namespace App\Dto\Tasks;

use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DTO para filtros de tareas
 */
class TaskFilterDto
{
    #[SerializedName('q')]
    public ?string $searchTerm = null;

    #[Assert\Choice(['pending', 'in_progress', 'completed', 'cancelled'])]
    public ?string $status = null;

    #[Assert\Choice(['low', 'medium', 'high'])]
    public ?string $priority = null;

    #[Assert\Positive]
    public ?int $assignedTo = null;

    #[Assert\Type(\DateTimeImmutable::class)]
    public ?DateTimeImmutable $dueFrom = null;

    #[Assert\Type(\DateTimeImmutable::class)]
    public ?DateTimeImmutable $dueTo = null;

    #[Assert\Type(\DateTimeImmutable::class)]
    public ?DateTimeImmutable $createdFrom = null;

    #[Assert\Type(\DateTimeImmutable::class)]
    public ?DateTimeImmutable $createdTo = null;

    #[Assert\All([
        new Assert\Type('string'),
        new Assert\Length(min: 1, max: 50)
    ])]
    public ?array $categories = null;

    public bool $includeInactive = false;
}
