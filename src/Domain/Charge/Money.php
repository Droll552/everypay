<?php

declare(strict_types=1);

namespace App\Domain\Charge;

use InvalidArgumentException;

final class Money
{

    private int $amount;
    private string $currency;

    public function __construct(int $amount, string $currency)
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount cannot be negative');
        }

        $currency = strtoupper(trim($currency));

        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException('Currency must be a 3-letter ISO 4217 code');
        }

        $this->amount = $amount;
        $this->currency = $currency;
    }

    public function getAmount(): int
    {

        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;

    }

    public function __toString(): string
    {
        return sprintf('%d %s', $this->amount, $this->currency);
    }
}