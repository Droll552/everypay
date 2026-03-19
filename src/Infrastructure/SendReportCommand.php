<?php

declare(strict_types=1);

namespace App\Infrastructure;

use App\Application\Command\SendChargeReportCommand;
use App\Application\Command\SendChargeReportHandler;
use App\Application\Command\SendChargeReportRequest;
use InvalidArgumentException;
use RuntimeException;

/**
 * CLI entry point for sending charge reports.
 *
 * Usage:
 *   php bin/console report:send <merchant_id> <from_date> <to_date>
 *
 * Example:
 *   php bin/console report:send merchant-1 2024-01-01 2024-01-31
 */
final class SendReportCommand
{
    public function __construct(
        private readonly SendChargeReportHandler $handler,
    ) {
    }

    /**
     * @param string[] $args CLI arguments after the command name.
     */
    public function run(array $args): int
    {
        if (count($args) < 3) {
            $this->printUsage();
            return 1;
        }

        [$merchantId, $fromRaw, $toRaw] = $args;

        try {
            // Validate all CLI input via the request DTO.
            $dto = SendChargeReportRequest::fromStrings($merchantId, $fromRaw, $toRaw);
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());
            return 1;
        }

        try {
            $this->handler->handle(
                new SendChargeReportCommand($dto->merchantId, $dto->from, $dto->to)
            );
            $this->info(sprintf('Report sent for merchant "%s" (%s – %s).', $merchantId, $fromRaw, $toRaw));
            return 0;
        } catch (RuntimeException $e) {
            $this->error($e->getMessage());
            return 1;
        }
    }

    private function printUsage(): void
    {
        echo "Usage: php bin/console report:send <merchant_id> <from_date> <to_date>\n";
        echo "       Dates must be in YYYY-MM-DD format.\n";
        echo "Example: php bin/console report:send merchant-stripe-1 2024-01-01 2024-01-31\n";
    }

    private function info(string $message): void
    {
        echo '[INFO] ' . $message . "\n";
    }

    private function error(string $message): void
    {
        fwrite(STDERR, '[ERROR] ' . $message . "\n");
    }
}
