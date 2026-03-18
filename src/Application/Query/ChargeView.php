<?php

declare(strict_types= 1);

namespace App\Application\Query;

/**
 * Read-model DTO for a single charge.
 * Deliberately flat — callers get plain data, not domain objects.
 */
final class ChargeView {
    public function __construct(
        public readonly string $chargeId, 
        public readonly string $merchantId,
        public readonly int $amount,
        public readonly string $currency,
        public readonly string $status, // enum?? 
        public readonly string $pspReference,
        public readonly string $createdAt
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'charge_id'     => $this->chargeId,
            'merchant_id'   => $this->merchantId,
            'amount'        => $this->amount,
            'currency'      => $this->currency,
            'status'        => $this->status,
            'psp_reference' => $this->pspReference,
            'created_at'    => $this->createdAt,
        ];
    }
}