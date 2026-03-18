<?php

declare(strict_types= 1);

namespace App\Application\Command;

final class CreateChargeResult{
    public function __construct(
        public readonly string $chargeId,
        public readonly string $status,
        public readonly string $pspReference,
        public readonly ?string $errorMessage
    )
    {}
}