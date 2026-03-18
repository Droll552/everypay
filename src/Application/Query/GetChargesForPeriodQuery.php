<?php

declare(strict_types=1);

namespace App\Application\Query;

use DateTimeImmutable;

/**
 * Query DTO: fetch all charges for a merchant within a time window.
 */
final class GetChargesForPeriodQuery
{
    public function __construct(
        public readonly string $merchantId,
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
    ) {
    }
}
