<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Immutable command DTO for the charge endpoint.
 */
final class CreateChargeCommand {
    /**
     * @param array<string,mixed> $credentials Provider-specific payload.
     */
    public function __construct(
        public readonly string $merchantId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly array $credentials,
    ) {}
}