<?php declare(strict_types=1);

namespace App\Mapper;

use App\Dto\TaskCreateInput;
use App\Dto\TaskResponseDto;
use App\Dto\TaskUpdateInput;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\UserRepository;
use AutoMapperPlus\AutoMapperPlusBundle\AutoMapperConfiguratorInterface;
use AutoMapperPlus\Configuration\AutoMapperConfig;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use AutoMapperPlus\MappingOperation\Operation;
use DateTimeImmutable;

class AutoMapperConfigFactory implements AutoMapperConfiguratorInterface
{
    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public static function create(): AutoMapperConfigInterface
    {
        return new AutoMapperConfig();
    }

    public function configure(AutoMapperConfigInterface $config): void
    {
        // Mapeo de Task a array para la respuesta JSON
        $config->registerMapping(Task::class, 'array')
            ->forMember('id', function(Task $task) {
                return $task->getId();
            })
            ->forMember('title', function(Task $task) {
                return $task->getTitle();
            })
            ->forMember('description', function(Task $task) {
                return $task->getDescription();
            })
            ->forMember('status', function(Task $task) {
                return $task->getStatus();
            })
            ->forMember('priority', function(Task $task) {
                return $task->getPriority();
            })
            ->forMember('dueDate', function(Task $task) {
                return $task->getDueDate()?->format('Y-m-d H:i:s');
            })
            ->forMember('createdAt', function(Task $task) {
                return $task->getCreatedAt()->format('Y-m-d H:i:s');
            })
            ->forMember('updatedAt', function(Task $task) {
                return $task->getUpdatedAt()->format('Y-m-d H:i:s');
            })
            ->forMember('assignedTo', function(Task $task) {
                $user = $task->getAssignedTo();
                return $user ? [
                    'id' => $user->getId(),
                    'email' => $user->getEmail(),
                    'name' => $user->getName()
                ] : null;
            })
            ->forMember('categories', function(Task $task) {
                return $task->getCategories();
            })
            ->forMember('active', function(Task $task) {
                return $task->isActive();
            });

        // Mapeo de TaskCreateInput a Task
        $config->registerMapping(TaskCreateInput::class, Task::class)
            ->forMember('id', Operation::ignore())
            ->forMember('dueDate', function($source) {
                return $this->parseDateString($source->dueDate);
            })
            ->forMember('assignedTo', function (TaskCreateInput $source) {
                return $source->assignedTo ? $this->userRepository->find($source->assignedTo) : null;
            })
            ->forMember('createdAt', Operation::ignore())
            ->forMember('updatedAt', Operation::ignore())
            ->forMember('deletedAt', Operation::ignore())
            ->forMember('isActive', Operation::ignore());

        // Mapeo de TaskUpdateInput a Task
        $config->registerMapping(TaskUpdateInput::class, Task::class)
            ->forMember('id', Operation::ignore())
            ->forMember('dueDate', function($source) {
                return $this->parseDateString($source->dueDate);
            })
            ->forMember('assignedTo', function (TaskUpdateInput $source) {
                return $source->assignedTo ? $this->userRepository->find($source->assignedTo) : null;
            })
            ->forMember('createdAt', Operation::ignore())
            ->forMember('updatedAt', Operation::ignore())
            ->forMember('deletedAt', Operation::ignore())
            ->forMember('isActive', Operation::ignore());

        // Mapeo de Task a TaskResponseDto
        $config->registerMapping(Task::class, TaskResponseDto::class)
            ->forMember('dueDate', function(Task $source) {
                return $source->getDueDate()?->format('Y-m-d\TH:i:s.u\Z');
            })
            ->forMember('createdAt', function(Task $source) {
                return $source->getCreatedAt()->format('Y-m-d\TH:i:s.u\Z');
            })
            ->forMember('updatedAt', function(Task $source) {
                return $source->getUpdatedAt()->format('Y-m-d\TH:i:s.u\Z');
            })
            ->forMember('assignedTo', function(Task $source) {
                $user = $source->getAssignedTo();
                return $user ? [
                    'id' => $user->getId(),
                    'name' => $user->getName() ?? $user->getEmail()
                ] : null;
            });
    }

    /**
     * Parsea una cadena de fecha a DateTimeImmutable
     *
     * @param string|null $dateString
     * @return DateTimeImmutable|null
     */
    private function parseDateString(?string $dateString): ?DateTimeImmutable
    {
        if ($dateString === null || trim($dateString) === '') {
            return null;
        }

        try {
            return new DateTimeImmutable($dateString);
        } catch (\Exception $e) {
            // Si no se puede parsear, retornar null
            return null;
        }
    }
}
