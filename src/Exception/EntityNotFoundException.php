<?php declare(strict_types=1);

namespace App\Exception;

class EntityNotFoundException extends \RuntimeException
{
    public function __construct(string $entity, $id = null)
    {
        $message = $id ? sprintf('%s con id %s no encontrado', $entity, $id) : sprintf('%s no encontrado', $entity);
        parent::__construct($message);
    }
}
