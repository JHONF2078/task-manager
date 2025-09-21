<?php declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: 'App\Repository\TaskRepository')]
#[ORM\HasLifecycleCallbacks]
class Task
{
    public const STATUS_PENDIENTE   = 'pendiente';
    public const STATUS_EN_PROGRESO = 'en_progreso';
    public const STATUS_COMPLETADA  = 'completada';

    public const PRIORITY_BAJA  = 'baja';
    public const PRIORITY_MEDIA = 'media';
    public const PRIORITY_ALTA  = 'alta';

    #[ORM\Id, ORM\GeneratedValue, ORM\Column(type: 'integer')]
    private int $id;

    #[ORM\Column(type: 'string')]
    #[Assert\NotBlank(message: 'El título es obligatorio')]
    #[Assert\Length(max: 255, maxMessage: 'El título no puede exceder {{ limit }} caracteres')]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 5000, maxMessage: 'La descripción es demasiado larga')]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: 'string')]
    #[Assert\Choice(callback: 'validStatuses', message: 'Estado inválido')]
    private string $status;

    #[ORM\Column(type: 'string')]
    #[Assert\Choice(callback: 'validPriorities', message: 'Prioridad inválida')]
    private string $priority;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $assignedTo = null;

    #[ORM\Column(type: 'json', nullable: true)]
    #[Assert\Type(type: 'array', message: 'categories debe ser un array')]
    private ?array $categories = [];

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $deletedAt = null;

    public static function validStatuses() : array
    {
        return [self::STATUS_PENDIENTE, self::STATUS_EN_PROGRESO, self::STATUS_COMPLETADA];
    }

    public static function validPriorities() : array
    {
        return [self::PRIORITY_BAJA, self::PRIORITY_MEDIA, self::PRIORITY_ALTA];
    }

    #[ORM\PrePersist]
    public function onPrePersist() : void
    {
        if (!isset($this->createdAt)) {
            $this->createdAt = new \DateTimeImmutable();
        }
        $this->updatedAt = new \DateTimeImmutable();
        if (!in_array($this->status ?? '', self::validStatuses(), true)) {
            $this->status = self::STATUS_PENDIENTE;
        }
        if (!in_array($this->priority ?? '', self::validPriorities(), true)) {
            $this->priority = self::PRIORITY_MEDIA;
        }
        if ($this->categories === null) {
            $this->categories = [];
        }
    }

    #[ORM\PreUpdate]
    public function onPreUpdate() : void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    // Getters y setters...
    public function getId() : int
    {
        return $this->id;
    }
    public function getTitle() : string
    {
        return $this->title;
    }
    public function setTitle(string $title) : void
    {
        $this->title = $title;
    }
    public function getDescription() : ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description) : void
    {
        $this->description = $description;
    }
    public function getCreatedAt() : \DateTimeInterface
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeInterface $createdAt) : void
    {
        $this->createdAt = $createdAt;
    }
    public function getUpdatedAt() : \DateTimeInterface
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTimeInterface $updatedAt) : void
    {
        $this->updatedAt = $updatedAt;
    }
    public function getStatus() : string
    {
        return $this->status;
    }
    public function setStatus(string $status) : void
    {
        if (!in_array($status, self::validStatuses(), true)) {
            throw new \InvalidArgumentException('Estado de tarea inválido');
        }
        $this->status = $status;
    }
    public function getPriority() : string
    {
        return $this->priority;
    }
    public function setPriority(string $priority) : void
    {
        if (!in_array($priority, self::validPriorities(), true)) {
            throw new \InvalidArgumentException('Prioridad de tarea inválida');
        }
        $this->priority = $priority;
    }
    public function getDueDate() : ?\DateTimeInterface
    {
        return $this->dueDate;
    }
    public function setDueDate(?\DateTimeInterface $dueDate) : void
    {
        $this->dueDate = $dueDate;
    }
    public function getAssignedTo() : ?User
    {
        return $this->assignedTo;
    }
    public function setAssignedTo(?User $user) : void
    {
        $this->assignedTo = $user;
    }
    public function getCategories() : array
    {
        return $this->categories ?? [];
    }
    public function setCategories(?array $categories) : void
    {
        $this->categories = $categories;
    }
    public function isActive() : bool
    {
        return $this->isActive;
    }
    public function deactivate() : void
    {
        $this->isActive  = false;
        $this->deletedAt = new \DateTimeImmutable();
    }
    public function activate() : void
    {
        $this->isActive  = true;
        $this->deletedAt = null;
    }
    public function getDeletedAt() : ?\DateTimeInterface
    {
        return $this->deletedAt;
    }
}
