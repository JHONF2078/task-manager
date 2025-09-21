<?php
namespace App\Exception;
class ValidationException extends \InvalidArgumentException {
    private array $errors;
    public function __construct(array $errors, string $message = 'Validation failed') {
        parent::__construct($message);
        $this->errors = $errors;
    }
    public function getErrors(): array { return $this->errors; }
    public function getViolations(): array { return $this->errors; }
}
