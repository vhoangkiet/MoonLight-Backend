<?php

namespace App\Exceptions;

use RuntimeException;

class DomainException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public readonly int $status,
        public readonly string $translationKey,
        public readonly ?string $code = null,
        public readonly array $context = [],
        ?string $message = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message ?? $translationKey, previous: $previous);
    }
}

