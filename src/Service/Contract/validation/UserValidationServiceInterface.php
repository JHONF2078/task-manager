<?php

declare(strict_types=1);

namespace App\Service\Contract\validation;

/**
 * Interfaz para el servicio de validación de usuarios
 */
interface UserValidationServiceInterface
{
    /**
     * Valida y normaliza los datos de usuario.
     * @param array $data
     * @return array{dto: object, normalizedData: array}
     * @throws \App\Exception\ValidationException
     */
    public function validateAndNormalizeUserData(array $data): array;

    /**
     * Normaliza los roles de usuario.
     * @param array|null $roles
     * @return array
     */
    public function normalizeRoles(?array $roles = null): array;
}

