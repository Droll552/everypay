<?php

declare(strict_types=1);

namespace App\Infrastructure\PSP;

use App\Domain\Charge\Money;
use App\Domain\Shared\PSP\PaymentServiceProviderInterface;
use App\Domain\Shared\PSP\PspResult;
use InvalidArgumentException;

/**
 * Simulates Stripe-like card payment processing.
 * Required credentials: card_number, cvv, expiry_month, expiry_year.
 */
final class FakeStripe implements PaymentServiceProviderInterface
{
    // Simulate a declined card for testing failure paths.
    private const DECLINED_CARD = '4000000000000002';

    public function charge(Money $amount, array $credentials): PspResult
    {
        $this->validateCredentials($credentials);

        $reference = 'stripe_' . bin2hex(random_bytes(8));

        if ($credentials['card_number'] === self::DECLINED_CARD) {
            return PspResult::failure($reference, 'Your card was declined.');
        }

        return PspResult::success($reference);
    }

    /**
     * @param array<string,mixed> $credentials
     */
    private function validateCredentials(array $credentials): void
    {
        $required = ['card_number', 'cvv', 'expiry_month', 'expiry_year'];

        foreach ($required as $field) {
            if (empty($credentials[$field])) {
                throw new InvalidArgumentException(
                    sprintf('FakeStripe requires "%s" in credentials.', $field)
                );
            }
        }
    }
}
