<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;

final class ChargeEndpointTest extends IntegrationTestCase
{
    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    private function makeRequest(
        string $method,
        string $path,
        array $body = [],
        ?string $apiKey = null,
    ): Response {
        $headers = ['content-type' => 'application/json'];

        if ($apiKey !== null) {
            $headers['authorization'] = 'Bearer ' . $apiKey;
        }

        $request = new Request($method, $path, $headers, $body);
        $router = $this->container->getRouter();

        return $router->dispatch($request);
    }

    // ------------------------------------------------------------------
    // Authentication
    // ------------------------------------------------------------------

    public function test_charge_returns_401_when_no_auth_header(): void
    {
        $response = $this->makeRequest('POST', '/charges', [
            'amount' => 1000,
            'currency' => 'EUR',
            'credentials' => ['card_number' => '4111111111111111', 'cvv' => '123', 'expiry_month' => '12', 'expiry_year' => '2030'],
        ]);

        $this->assertSame(401, $response->getStatusCode());
    }

    public function test_charge_returns_401_with_invalid_api_key(): void
    {
        $response = $this->makeRequest('POST', '/charges', [
            'amount' => 1000,
            'currency' => 'EUR',
            'credentials' => ['card_number' => '4111111111111111', 'cvv' => '123', 'expiry_month' => '12', 'expiry_year' => '2030'],
        ], 'invalid_key_000');

        $this->assertSame(401, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // FakeStripe — successful charge
    // ------------------------------------------------------------------

    public function test_stripe_charge_succeeds_with_valid_card(): void
    {
        $response = $this->makeRequest('POST', '/charges', [
            'amount' => 5000,
            'currency' => 'EUR',
            'credentials' => [
                'card_number' => '4111111111111111',
                'cvv' => '123',
                'expiry_month' => '12',
                'expiry_year' => '2030',
            ],
        ], 'test_stripe_key_abc123');

        $data = $response->getData();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('successful', $data['status']);
        $this->assertStringStartsWith('stripe_', $data['psp_reference']);
        $this->assertNotEmpty($data['charge_id']);
        $this->assertNull($data['error_message']);
    }

    // ------------------------------------------------------------------
    // FakeStripe — declined card
    // ------------------------------------------------------------------

    public function test_stripe_charge_fails_with_declined_card(): void
    {
        $response = $this->makeRequest('POST', '/charges', [
            'amount' => 5000,
            'currency' => 'EUR',
            'credentials' => [
                'card_number' => '4000000000000002',
                'cvv' => '123',
                'expiry_month' => '12',
                'expiry_year' => '2030',
            ],
        ], 'test_stripe_key_abc123');

        $data = $response->getData();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('failed', $data['status']);
        $this->assertNotNull($data['error_message']);
    }

    // ------------------------------------------------------------------
    // FakePaypal — successful charge
    // ------------------------------------------------------------------

    public function test_paypal_charge_succeeds_with_valid_credentials(): void
    {
        $response = $this->makeRequest('POST', '/charges', [
            'amount' => 2500,
            'currency' => 'USD',
            'credentials' => [
                'email' => 'user@example.com',
                'password' => 'secret123',
            ],
        ], 'test_paypal_key_xyz789');

        $data = $response->getData();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('successful', $data['status']);
        $this->assertStringStartsWith('paypal_', $data['psp_reference']);
    }

    // ------------------------------------------------------------------
    // FakePaypal — blocked account
    // ------------------------------------------------------------------

    public function test_paypal_charge_fails_with_blocked_account(): void
    {
        $response = $this->makeRequest('POST', '/charges', [
            'amount' => 2500,
            'currency' => 'USD',
            'credentials' => [
                'email' => 'blocked@example.com',
                'password' => 'secret123',
            ],
        ], 'test_paypal_key_xyz789');

        $data = $response->getData();

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('failed', $data['status']);
        $this->assertNotNull($data['error_message']);
    }

    // ------------------------------------------------------------------
    // Validation
    // ------------------------------------------------------------------

    public function test_charge_returns_400_when_amount_missing(): void
    {
        $response = $this->makeRequest('POST', '/charges', [
            'currency' => 'EUR',
            'credentials' => ['card_number' => '4111111111111111', 'cvv' => '123', 'expiry_month' => '12', 'expiry_year' => '2030'],
        ], 'test_stripe_key_abc123');

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_charge_returns_400_when_credentials_missing(): void
    {
        $response = $this->makeRequest('POST', '/charges', [
            'amount' => 1000,
            'currency' => 'EUR',
        ], 'test_stripe_key_abc123');

        $this->assertSame(400, $response->getStatusCode());
    }

    public function test_charge_returns_400_when_amount_is_negative(): void
    {
        $response = $this->makeRequest('POST', '/charges', [
            'amount' => -100,
            'currency' => 'EUR',
            'credentials' => ['card_number' => '4111111111111111', 'cvv' => '123', 'expiry_month' => '12', 'expiry_year' => '2030'],
        ], 'test_stripe_key_abc123');

        $this->assertSame(400, $response->getStatusCode());
    }

    // ------------------------------------------------------------------
    // Persistence — charge is saved to the database
    // ------------------------------------------------------------------

    public function test_successful_charge_is_persisted(): void
    {
        $this->makeRequest('POST', '/charges', [
            'amount' => 3000,
            'currency' => 'EUR',
            'credentials' => [
                'card_number' => '4111111111111111',
                'cvv' => '123',
                'expiry_month' => '12',
                'expiry_year' => '2030',
            ],
        ], 'test_stripe_key_abc123');

        $stmt = $this->pdo->query('SELECT COUNT(*) as cnt FROM charges');
        $count = (int) $stmt->fetchColumn();

        $this->assertSame(1, $count);
    }

    // ------------------------------------------------------------------
    // Routing
    // ------------------------------------------------------------------

    public function test_unknown_route_returns_404(): void
    {
        $response = $this->makeRequest('GET', '/nonexistent');
        $this->assertSame(404, $response->getStatusCode());
    }
}
