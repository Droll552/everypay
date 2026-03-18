<?php

declare(strict_types=1);

namespace App\Domain\Merchant;

enum PspType: string
{
    case FakeStripe = 'fake_stripe';
    case FakePaypal = 'fake_paypal';
}