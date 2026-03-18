<?php

declare(strict_types=1);

namespace App\Domain\Charge;

use App\Domain\Shared\ValueIbject\MerchantId;
use DateTimeImmutable;

final class Charge
{
    public function __construct(
        private readonly ChargeId $id,
        private readonly MerchantId $merchantId,
        private readonly Money $amount,
        private readonly ChargeStatus $status,
        private readonly string $pspReference,
        private readonly DateTimeImmutable $createdAt
    ) {
    }

    public static function create(
        ChargeId $id,
        MerchantId $merchantId,
        Money $amount,
        ChargeStatus $status,
        string $pspReference,
        DateTimeImmutable $createdAt
    ): self {
        return new self($id, $merchantId, $amount, $status, $pspReference, $createdAt);
    }

    public function getId(): ChargeId
    {
        return $this->id;
    }

    public function getMerchantId(): MerchantId
    {
        return $this->merchantId;
    }

    public function getAmount(): Money
    {
        return $this->amount;
    }

    public function getStatus(): ChargeStatus
    {
        return $this->status;
    }

    public function getPspReference(): string
    {
        return $this->pspReference;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}