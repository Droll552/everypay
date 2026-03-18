<?php

declare(strict_types=1);

namespace App\Application\Command;

use InvalidArgumentException; 

/**
 * Validated request DTO for creating a charge.
 */
final class CreateChargeRequest
{
    /** @param array<string,mixed> $credentials */
    private function __construct(
        public readonly int $amount,
        public readonly string $currency,
        public readonly array $credentials,
    ) {}

    /**
     * @param array<string,mixed> $data
     * @throws InvalidArgumentException
     */
    public static function fromArray(array $data): self {
        if (!isset($data['amount'])) {
            throw new InvalidArgumentException('Field "amount" is required.');
        }

        if (!is_int($data['amount']) || $data['amount'] <= 0) {
            throw new InvalidArgumentException('"amount" must be a positive integer (minor units, e.g. 5000 = €50.00).');
        }

        if (empty($data['currency']) || !is_string($data['currency'])) {
            throw new InvalidArgumentException('Field "currency" is required and must be a string.');
        }

        if (!isset($data['credentials']) || !is_array($data['credentials']) || empty($data['credentials'])) {
            throw new InvalidArgumentException('Field "credentials" is required and must be a non-empty object.');
        }

        return new self(
            amount:      $data['amount'],
            currency:    strtoupper(trim($data['currency'])),
            credentials: $data['credentials'],
        );
    }
}