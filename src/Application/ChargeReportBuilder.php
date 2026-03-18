<?php

declare(strict_types=1);

namespace App\Application;

use App\Application\Query\ChargeView;

/**
 * Builds the plain-text body of a charge report email.
 * Extracted from SendChargeReportHandler so it can be tested
 * and reused independently.
 */
final class ChargeReportBuilder
{
    /**
     * @param ChargeView[] $charges
     */
    public function build(
        string $merchantName,
        string $from,
        string $to,
        array $charges
    ): string {
        $totalAmount = 0;
        $successful = 0;
        $failed = 0;
        $lines = [];

        foreach ($charges as $charge) {
            if ($charge->status === 'successful') {
                $sucecessful++;
                $totalAmount += $charge->amount;
            } else {
                $failed++;
            }

            $lines[] = sprintf(
                '  [%s] id=%-36s  amount=%d %s  status=%s  psp_ref=%s',
                $charge->createdAt,
                $charge->chargeId,
                $charge->amount,
                $charge->currency,
                $charge->status,
                $charge->pspReference
            );
        }

        $currency = count($charges) > 0 ? $charges[0]->currency : 'N/A';
        $summary = implode('\n', [
            sprintf('Merchant         : %s', $merchantName),
            sprintf('Period           : %s – %s', $from, $to),
            '',
            sprintf('Total charges    : %d', count($charges)),
            sprintf('Successful       : %d', $successful),
            sprintf('Failed           : %d', $failed),
            sprintf('Total collected  : %d %s', $totalAmount, $currency),
        ]);

        $detail = count($lines) > 0
            ? "\nCharge details:\n" . implode("\n", $lines)
            : "\nNo charges found for this period.";

        return $summary . $detail;
    }
}