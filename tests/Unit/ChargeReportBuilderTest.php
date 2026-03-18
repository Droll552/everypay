<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Query\ChargeView;
use App\Application\ChargeReportBuilder;
use PHPUnit\Framework\TestCase;

final class ChargeReportBuilderTest extends TestCase
{
    private ChargeReportBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new ChargeReportBuilder();
    }

    public function test_builds_correct_summary_with_mixed_charges(): void
    {
        $charges = [
            $this->makeCharge('chg-1', 1000, 'EUR', 'successful'),
            $this->makeCharge('chg-2', 2000, 'EUR', 'successful'),
            $this->makeCharge('chg-3', 500,  'EUR', 'failed'),
        ];

        $body = $this->builder->build('Acme Corp', '2024-06-01', '2024-06-30', $charges);

        $this->assertStringContainsString('Merchant         : Acme Corp', $body);
        $this->assertStringContainsString('Period           : 2024-06-01 – 2024-06-30', $body);
        $this->assertStringContainsString('Total charges    : 3', $body);
        $this->assertStringContainsString('Successful       : 2', $body);
        $this->assertStringContainsString('Failed           : 1', $body);
        $this->assertStringContainsString('Total collected  : 3000 EUR', $body);
    }

    public function test_shows_no_charges_message_when_list_is_empty(): void
    {
        $body = $this->builder->build('Acme Corp', '2024-06-01', '2024-06-30', []);

        $this->assertStringContainsString('Total charges    : 0', $body);
        $this->assertStringContainsString('No charges found for this period.', $body);
    }

    public function test_failed_charges_are_not_counted_in_total_amount(): void
    {
        $charges = [
            $this->makeCharge('chg-1', 9999, 'EUR', 'failed'),
            $this->makeCharge('chg-2', 1000, 'EUR', 'successful'),
        ];

        $body = $this->builder->build('Shop', '2024-01-01', '2024-01-31', $charges);

        $this->assertStringContainsString('Total collected  : 1000 EUR', $body);
    }

    public function test_charge_details_line_is_included_for_each_charge(): void
    {
        $charges = [
            $this->makeCharge('chg-abc', 500, 'USD', 'successful'),
        ];

        $body = $this->builder->build('Shop', '2024-01-01', '2024-01-31', $charges);

        $this->assertStringContainsString('chg-abc', $body);
        $this->assertStringContainsString('500', $body);
        $this->assertStringContainsString('successful', $body);
    }

    private function makeCharge(
        string $id,
        int $amount,
        string $currency,
        string $status,
    ): ChargeView {
        return new ChargeView(
            chargeId:     $id,
            merchantId:   'merchant-1',
            amount:       $amount,
            currency:     $currency,
            status:       $status,
            pspReference: 'stripe_' . $id,
            createdAt:    '2024-06-15 10:00:00',
        );
    }
}
