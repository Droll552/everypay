# Payment Gateway Service

Key design decisions:
- **DDD-lite**: Domain layer has zero infrastructure imports. Repositories are interfaces in the Domain, implemented in Infrastructure.
- **Strategy pattern** for PSPs: `PspRegistry` resolves the correct `PaymentServiceProviderInterface` at runtime based on the merchant's configured `PspType`.
- **No framework**: A minimal vanilla PHP router handles routing. The DI container is a hand-wired composition root — no reflection, no magic.
- **Storage abstraction**: Repositories are defined as interfaces. Swapping SQLite for PostgreSQL requires only a new `Infrastructure/Persistence` implementation.

---

## Requirements

- Docker & Docker Compose v2

---

## Running the Application

```bash
# 1. Clone and enter the project
git clone <repo-url> everypay && cd everypay

# 2. Build and start services
docker compose up --build -d

# The API is now available at http://localhost:8080
```

Migrations and seeding run automatically during the Docker build.


## API

### Charge a payment card

```
POST /charges
Authorization: Bearer <api_key>
Content-Type: application/json
```

**FakeStripe payload**
```json
{
  "amount": 5000,
  "currency": "EUR",
  "credentials": {
    "card_number": "4111111111111111",
    "cvv": "123",
    "expiry_month": "12",
    "expiry_year": "2030"
  }
}
```

**FakePaypal payload**
```json
{
  "amount": 2500,
  "currency": "USD",
  "credentials": {
    "email": "user@example.com",
    "password": "secret"
  }
}
```

> `amount` is in **minor units** (e.g. 5000 = €50.00).

**Response `201 Created`**
```json
{
  "charge_id": "4b3e1a2d-...",
  "status": "successful",
  "psp_reference": "stripe_a1b2c3d4e5f6",
  "error_message": null
}
```

**Response `201 Created` (declined)**
```json
{
  "charge_id": "...",
  "status": "failed",
  "psp_reference": "stripe_...",
  "error_message": "Your card was declined."
}
```
**Simulate a declined card (FakeStripe):** use card number `4000000000000002`.  
**Simulate a blocked account (FakePaypal):** use email `blocked@example.com`.

#### Example cURL calls

```bash
# Successful Stripe charge
curl -s -X POST http://localhost:8080/charges \
  -H "Authorization: Bearer test_stripe_key_abc123" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 5000,
    "currency": "EUR",
    "credentials": {
      "card_number": "4111111111111111",
      "cvv": "123",
      "expiry_month": "12",
      "expiry_year": "2030"
    }
  }'

# Declined card
curl -s -X POST http://localhost:8080/charges \
  -H "Authorization: Bearer test_stripe_key_abc123" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 1000,
    "currency": "EUR",
    "credentials": {
      "card_number": "4000000000000002",
      "cvv": "999",
      "expiry_month": "01",
      "expiry_year": "2025"
    }
  }'

# PayPal charge
curl -s -X POST http://localhost:8080/charges \
  -H "Authorization: Bearer test_paypal_key_xyz789" \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 2500,
    "currency": "USD",
    "credentials": {
      "email": "user@example.com",
      "password": "secret"
    }
  }'
```
## Charge Report CLI

The report command collects all charges for a merchant within a date range and writes the email to the log file.

```bash
docker compose exec php php bin/console report:send <merchant_id> <from> <to>
```

**Example — last 30 days:**
```bash
docker compose exec php php bin/console report:send merchant-stripe-1 2024-06-01 2024-06-30
```

**View the generated email:**
```bash
docker compose exec php cat /var/log/app/mail.log
```

---

## Running Tests

Tests use an **in-memory SQLite** database — no running containers required.

```bash
# Install dev dependencies
composer install

docker compose exec php composer test

