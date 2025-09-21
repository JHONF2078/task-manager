<?php declare(strict_types=1);

namespace App\Exception;

class ValidationException extends \InvalidArgumentException
{
    private array $errors;
    public function __construct(array $errors, string $message = '')
    {
        $this->errors = $errors;
        // Si hay errores, usa el primer mensaje; si no, usa el mensaje por defecto
        $msg = $message;
        if (!$msg && !empty($errors) && isset($errors[0]['message'])) {
            $msg = $errors[0]['message'];
        }
        parent::__construct($msg ?: 'Validation failed');
    }
    public function getErrors() : array
    {
        return $this->errors;
    }
    public function getViolations() : array
    {
        return $this->errors;
    }
}
