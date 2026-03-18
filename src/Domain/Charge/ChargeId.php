<?php

declare(strict_types=1);

namespace App\Domain\Charge;

use InvalidArgumentException;

final class ChargeId
{
    private string $value;

    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('ChargeId cannot be empty');
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

    public function equals(ChargeId $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}