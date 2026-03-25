<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class DomainException extends Exception
{
    /**
     * @var mixed|null
     */
    protected mixed $errors;

    /**
     * DomainException constructor.
     */
    public function __construct(
        string $message = '',
        int $code = Response::HTTP_BAD_REQUEST,
        mixed $errors = null
    ) {
        parent::__construct($message, $code);
        $this->errors = $errors;
    }

    /**
     * Get the extra errors.
     *
     * @return mixed|null
     */
    public function getErrors(): mixed
    {
        return $this->errors;
    }
}
