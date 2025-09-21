<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

class InvalidCredentialsException extends HttpException
{
    public function __construct(
        string $message = 'Email o contraseña incorrectos',
        int $code = 401,
        \Throwable $previous = null,
        array $headers = [],
        int $statusCode = 401
    )
    {
        parent::__construct($statusCode, $message, $previous, $headers, $code);
    }
}

