<?php declare(strict_types=1);

namespace App\Mapper\Auto;

use App\Dto\auth\AuthRequestDataDto;
use App\Dto\auth\AuthResponseDto;
use App\Dto\TaskCreateInput;
use App\Dto\TaskResponseDto;
use App\Dto\TaskUpdateInput;
use App\Dto\UserResponseDto;
use App\Entity\Task;
use App\Entity\User;
use App\Repository\UserRepository;
use AutoMapperPlus\AutoMapperPlusBundle\AutoMapperConfiguratorInterface;
use AutoMapperPlus\Configuration\AutoMapperConfig;
use AutoMapperPlus\Configuration\AutoMapperConfigInterface;
use AutoMapperPlus\MappingOperation\Operation;
use DateTimeImmutable;
use Exception;

class AutoMapperConfigFactory implements AutoMapperConfiguratorInterface
{
    private const DATE_FORMAT_ISO8601 = 'Y-m-d\TH:i:s.u\Z';

    public function __construct(private readonly UserRepository $userRepository)
    {
    }

    public static function create() : AutoMapperConfigInterface
    {
        return new AutoMapperConfig();
    }

    public function configure(AutoMapperConfigInterface $config) : void
    {
        $this->configureTaskMappings($config);
        $this->configureUserMappings($config);
        $this->configureAuthMappings($config);
    }

    /**
     * Configuración de mapeos relacionados con Task
     */
    private function configureTaskMappings(AutoMapperConfigInterface $config) : void
    {
        // Mapeo de TaskCreateInput a Task
        $config->registerMapping(TaskCreateInput::class, Task::class)
            ->forMember('id', Operation::ignore())
            ->forMember('dueDate', fn ($source) => $this->parseDateString($source->dueDate))
            ->forMember(
                'assignedTo',
                fn (TaskCreateInput $source) => $source->assignedTo ? $this->userRepository->find($source->assignedTo) : null
            )
            ->forMember('createdAt', Operation::ignore())
            ->forMember('updatedAt', Operation::ignore())
            ->forMember('deletedAt', Operation::ignore())
            ->forMember('isActive', Operation::ignore());

        // Mapeo de TaskUpdateInput a Task (extiende la configuración anterior)
        $config->registerMapping(TaskUpdateInput::class, Task::class)
            ->forMember('id', Operation::ignore())
            ->forMember('dueDate', fn ($source) => $this->parseDateString($source->dueDate))
            ->forMember(
                'assignedTo',
                fn (TaskUpdateInput $source) => $source->assignedTo ? $this->userRepository->find($source->assignedTo) : null
            )
            ->forMember('createdAt', Operation::ignore())
            ->forMember('updatedAt', Operation::ignore())
            ->forMember('deletedAt', Operation::ignore())
            ->forMember('isActive', Operation::ignore());

        // Mapeo de Task a TaskResponseDto (fuente única de verdad para respuestas)
        $config->registerMapping(Task::class, TaskResponseDto::class)
            ->forMember(
                'dueDate',
                fn (Task $task) => $task->getDueDate()?->format(self::DATE_FORMAT_ISO8601)
            )
            ->forMember(
                'createdAt',
                fn (Task $task) => $task->getCreatedAt()->format(self::DATE_FORMAT_ISO8601)
            )
            ->forMember(
                'updatedAt',
                fn (Task $task) => $task->getUpdatedAt()->format(self::DATE_FORMAT_ISO8601)
            )
            ->forMember(
                'assignedTo',
                fn (Task $task) => $this->mapUserToArray($task->getAssignedTo())
            );
    }

    /**
     * Configuración de mapeos relacionados con User
     */
    private function configureUserMappings(AutoMapperConfigInterface $config) : void
    {
        // Mapeo de User a UserResponseDto (DTO unificado para todas las respuestas de usuario)
        $config->registerMapping(User::class, UserResponseDto::class)
            ->forMember('id', fn (User $user) => $user->getId())
            ->forMember('email', fn (User $user) => $user->getEmail())
            ->forMember('name', fn (User $user) => $user->getName())
            ->forMember('roles', fn (User $user) => $user->getRoles())
            ->forMember('active', fn (User $user) => $user->isActive());
    }

    /**
     * Configuración de mapeos relacionados con autenticación
     */
    private function configureAuthMappings(AutoMapperConfigInterface $config) : void
    {
        // Mapeo de AuthRequestDataDto a AuthResponseDto
        $config->registerMapping(AuthRequestDataDto::class, AuthResponseDto::class)
            ->forMember('token', fn (AuthRequestDataDto $src) => $src->accessToken)
            ->forMember('token_type', fn () => 'Bearer')
            ->forMember('expires_in', fn (AuthRequestDataDto $src) => $src->accessTtl)
            ->forMember(
                'issued_at',
                fn (AuthRequestDataDto $src) => new DateTimeImmutable('@' . $src->issuedAt)
            )
            ->forMember(
                'expires_at',
                fn (AuthRequestDataDto $src) => new DateTimeImmutable('@' . $src->expiresAt)
            )
            ->forMember(
                'user',
                fn (AuthRequestDataDto $src, $mapper) => $mapper->map($src->user, UserResponseDto::class)
            );
    }

    /**
     * Mapea un usuario a un array simple para respuestas anidadas
     */
    private function mapUserToArray(?User $user) : ?array
    {
        if ($user === null) {
            return null;
        }

        return [
            'id'    => $user->getId(),
            'name'  => $user->getName() ?? $user->getEmail(),
            'email' => $user->getEmail()
        ];
    }

    /**
     * Parsea una cadena de fecha a DateTimeImmutable
     */
    private function parseDateString(?string $dateString) : ?DateTimeImmutable
    {
        if (empty($dateString)) {
            return null;
        }

        try {
            return new DateTimeImmutable($dateString);
        } catch (Exception) {
            return null;
        }
    }
}
