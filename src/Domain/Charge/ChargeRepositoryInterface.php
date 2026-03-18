<?php

declare(strict_types=1);

namespace App\Domain\Charge;

use App\Domain\Shared\MerchantId;
use DateTimeImmutable;

interface ChargeRepositoryInterface
{
    public function save(Charge $charge): void;

    public function findById(ChargeId $id): ?Charge;

    public function findByMerchantAndPeriod(
        MerchantId $merchantId,
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): array;
}