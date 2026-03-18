<?php

declare(strict_types=1);

namespace App\Domain\Shared\PSP;

use App\Domain\Charge\Money;

interface PaymentServiceProviderInterface
{
    /**
     * @param Money $amount
     * @param array<string,mixed>  $credentials
     * @return PspResult
     */
    public function charge(Money $amount, array $credentials): PspResult;
}