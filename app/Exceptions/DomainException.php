<?php

namespace App\Exceptions;

use RuntimeException;

class DomainException extends RuntimeException
{
    /**
     * @var int
     */
    public $status;

    /**
     * @var string
     */
    public $translationKey;

    /**
     * @var string|null
     */
    public $domainCode;

    /**
     * @var array<string,mixed>
     */
    public $context;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        int $status,
        string $translationKey,
        ?string $code = null,
        array $context = [],
        ?string $message = null,
        ?\Throwable $previous = null,
    ) {
        $this->status = $status;
        $this->translationKey = $translationKey;
        $this->domainCode = $code;
        $this->context = $context;

        parent::__construct($message ?? $translationKey, previous: $previous);
    }
}
