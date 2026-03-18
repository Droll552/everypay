<?php

declare(strict_types=1);

namespace App\Application\Command;

/**
 * Response DTO returned from CreateChargeHandler.
 */
final class CreateChargeResponse
{
    public function __construct(
        public readonly string $chargeId,
        public readonly string $status,
        public readonly string $pspReference,
        public readonly ?string $errorMessage
    ) {
    }

    /** @return array<string,mixed> */
    public function toArray(): array
    {
        return [
            'charge_id' => $this->chargeId,
            'status' => $this->status,
            'psp_reference' => $this->pspReference,
            'error_message' => $this->errorMessage
        ];
    }
}