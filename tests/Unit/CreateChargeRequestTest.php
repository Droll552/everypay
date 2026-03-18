<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Command\CreateChargeRequest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CreateChargeRequestTest extends TestCase
{
    public function test_creates_request_with_valid_stripe_payload(): void
    {
        $dto = CreateChargeRequest::fromArray([
            'amount' => 5000,
            'currency' => 'eur',
            'credentials' => ['card_number' => '4111111111111111', 'cvv' => '123'],
        ]);

        $this->assertSame(5000, $dto->amount);
        $this->assertSame('EUR', $dto->currency); // normalised to uppercase
        $this->assertSame('4111111111111111', $dto->credentials['card_number']);
    }

    public function test_throws_when_amount_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"amount" is required/');
        CreateChargeRequest::fromArray(['currency' => 'EUR', 'credentials' => ['x' => 'y']]);
    }

    public function test_throws_when_amount_is_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CreateChargeRequest::fromArray(['amount' => 0, 'currency' => 'EUR', 'credentials' => ['x' => 'y']]);
    }

    public function test_throws_when_amount_is_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CreateChargeRequest::fromArray(['amount' => -1, 'currency' => 'EUR', 'credentials' => ['x' => 'y']]);
    }

    public function test_throws_when_amount_is_a_float(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CreateChargeRequest::fromArray(['amount' => 10.5, 'currency' => 'EUR', 'credentials' => ['x' => 'y']]);
    }

    public function test_throws_when_currency_is_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CreateChargeRequest::fromArray(['amount' => 1000, 'credentials' => ['x' => 'y']]);
    }

    public function test_throws_when_credentials_are_missing(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"credentials" is required/');
        CreateChargeRequest::fromArray(['amount' => 1000, 'currency' => 'EUR']);
    }

    public function test_throws_when_credentials_are_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CreateChargeRequest::fromArray(['amount' => 1000, 'currency' => 'EUR', 'credentials' => []]);
    }
}
