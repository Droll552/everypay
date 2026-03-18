<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use InvalidArgumentException;

final class MerchantId
{
    private string $value;

    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Merchant can not be empty');
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function equals(MerchantId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}