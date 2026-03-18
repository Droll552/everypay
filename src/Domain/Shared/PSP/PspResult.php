<?php

declare(strict_types=1);

namespace App\Domain\Shared\PSP;

final class PspResult
{
    private function __construct(
        private readonly bool $success,
        private readonly string $reference,
        private readonly ?string $errorMessage,
    ) {
    }

    public static function success(string $reference): self
    {
        return new self(true, $reference, null);
    }

    public static function failure(string $reference, string $errorMessage): self
    {
        return new self(false, $reference, $errorMessage);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getReference(): string
    {
        return $this->reference;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}