<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Application\Command\SendChargeReportCommand;
use DateTimeImmutable;
use RuntimeException;

final class SendChargeReportTest extends IntegrationTestCase
{
    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    private function insertCharge(
        string $merchantId,
        int $amount,
        string $currency,
        string $status,
        string $createdAt,
    ): void {
        $this->pdo->prepare(
            'INSERT INTO charges (id, merchant_id, amount, currency, status, psp_reference, created_at)
             VALUES (:id, :merchant_id, :amount, :currency, :status, :psp_reference, :created_at)'
        )->execute([
                    'id' => uniqid('chg_', true),
                    'merchant_id' => $merchantId,
                    'amount' => $amount,
                    'currency' => $currency,
                    'status' => $status,
                    'psp_reference' => 'stripe_' . uniqid(),
                    'created_at' => $createdAt,
                ]);
    }

    // ------------------------------------------------------------------
    // Tests
    // ------------------------------------------------------------------

    public function test_report_is_sent_for_existing_merchant(): void
    {
        $this->insertCharge('merchant-stripe-1', 1000, 'EUR', 'successful', '2024-06-15 10:00:00');
        $this->insertCharge('merchant-stripe-1', 2000, 'EUR', 'successful', '2024-06-20 11:00:00');
        $this->insertCharge('merchant-stripe-1', 500, 'EUR', 'failed', '2024-06-22 12:00:00');

        $command = new SendChargeReportCommand(
            'merchant-stripe-1',
            new DateTimeImmutable('2024-06-01 00:00:00'),
            new DateTimeImmutable('2024-06-30 23:59:59'),
        );

        $this->container->getSendReportHandler()->handle($command);

        $content = $this->readMailLog();

        $this->assertStringContainsString('Acme Corp', $content);
        $this->assertStringContainsString('Total charges    : 3', $content);
        $this->assertStringContainsString('Successful       : 2', $content);
        $this->assertStringContainsString('Failed           : 1', $content);
        $this->assertStringContainsString('Total collected  : 3000 EUR', $content);
    }

    public function test_report_contains_no_charges_message_when_period_is_empty(): void
    {
        $command = new SendChargeReportCommand(
            'merchant-stripe-1',
            new DateTimeImmutable('2020-01-01 00:00:00'),
            new DateTimeImmutable('2020-01-31 23:59:59'),
        );

        $this->container->getSendReportHandler()->handle($command);

        $this->assertStringContainsString('No charges found for this period.', $this->readMailLog());
    }

    public function test_report_excludes_charges_outside_period(): void
    {
        // This charge is in January — should NOT appear in the June report.
        $this->insertCharge('merchant-stripe-1', 9999, 'EUR', 'successful', '2024-01-01 10:00:00');
        // This one is in June — should appear.
        $this->insertCharge('merchant-stripe-1', 1000, 'EUR', 'successful', '2024-06-15 10:00:00');

        $command = new SendChargeReportCommand(
            'merchant-stripe-1',
            new DateTimeImmutable('2024-06-01 00:00:00'),
            new DateTimeImmutable('2024-06-30 23:59:59'),
        );

        $this->container->getSendReportHandler()->handle($command);

        $content = $this->readMailLog();

        $this->assertStringContainsString('Total collected  : 1000 EUR', $content);
        $this->assertStringNotContainsString('9999', $content);
    }

    public function test_report_throws_for_unknown_merchant(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Merchant.*not found/');

        $command = new SendChargeReportCommand(
            'non-existent-merchant',
            new DateTimeImmutable('2024-06-01'),
            new DateTimeImmutable('2024-06-30'),
        );

        $this->container->getSendReportHandler()->handle($command);
    }

    public function test_report_only_includes_charges_for_requested_merchant(): void
    {
        // Two merchants, same period — report must only show merchant-stripe-1's charge.
        $this->insertCharge('merchant-stripe-1', 1000, 'EUR', 'successful', '2024-06-15 10:00:00');
        $this->insertCharge('merchant-paypal-1', 5000, 'USD', 'successful', '2024-06-15 11:00:00');

        $command = new SendChargeReportCommand(
            'merchant-stripe-1',
            new DateTimeImmutable('2024-06-01 00:00:00'),
            new DateTimeImmutable('2024-06-30 23:59:59'),
        );

        $this->container->getSendReportHandler()->handle($command);

        $content = $this->readMailLog();

        $this->assertStringContainsString('Total charges    : 1', $content);
        $this->assertStringContainsString('Total collected  : 1000 EUR', $content);
    }

    public function test_report_email_is_addressed_to_merchant_email(): void
    {
        $command = new SendChargeReportCommand(
            'merchant-stripe-1',
            new DateTimeImmutable('2024-06-01 00:00:00'),
            new DateTimeImmutable('2024-06-30 23:59:59'),
        );

        $this->container->getSendReportHandler()->handle($command);

        $this->assertStringContainsString('billing@acme.example.com', $this->readMailLog());
    }
}
