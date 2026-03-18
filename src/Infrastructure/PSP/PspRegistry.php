<?php

declare(strict_types=1);

namespace App\Infrastructure\PSP;

use App\Domain\Merchant\PspType;
use App\Domain\Shared\PSP\PaymentServiceProviderInterface;
use RuntimeException;

final class PspRegistry
{
    /** @var array<string, PaymentServiceProviderInterface> */
    private array $providers = [];

    public function register(PspType $type, PaymentServiceProviderInterface $provider): void
    {
        $this->providers[$type->value] = $provider;
    }

    public function get(PspType $type): PaymentServiceProviderInterface
    {
        if (!isset($this->providers[$type->value])) {
            throw new RuntimeException(
                sprintf('No PSP registered for type "%s".', $type->value)
            );
        }

        return $this->providers[$type->value];
    }
}
