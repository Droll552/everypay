<?php

declare(strict_types=1);

namespace App\Application\Command;

use DateTimeImmutable;

final class SendChargeReportCommand
{
    public function __construct(
        public readonly string $merchantId,
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
    ) {
    }
}