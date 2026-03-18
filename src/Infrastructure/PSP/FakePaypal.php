<?php

declare(strict_types=1);

namespace App\Infrastructure\PSP;

use App\Domain\Charge\Money;
use App\Domain\Shared\PSP\PaymentServiceProviderInterface;
use App\Domain\Shared\PSP\PspResult;
use InvalidArgumentException;

/**
 * Simulates PayPal-like payment processing.
 * Required credentials: email, password.
 */
final class FakePaypal implements PaymentServiceProviderInterface
{
    // Simulate a blocked account for testing failure paths.
    private const BLOCKED_EMAIL = 'blocked@example.com';

    public function charge(Money $amount, array $credentials): PspResult
    {
        $this->validateCredentials($credentials);

        $reference = 'paypal_' . bin2hex(random_bytes(8));

        if ($credentials['email'] === self::BLOCKED_EMAIL) {
            return PspResult::failure($reference, 'This PayPal account has been restricted.');
        }

        return PspResult::success($reference);
    }

    /**
     * @param array<string,mixed> $credentials
     */
    private function validateCredentials(array $credentials): void
    {
        $required = ['email', 'password'];

        foreach ($required as $field) {
            if (empty($credentials[$field])) {
                throw new InvalidArgumentException(
                    sprintf('FakePaypal requires "%s" in credentials.', $field)
                );
            }
        }
    }
}
