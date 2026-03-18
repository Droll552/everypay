<?php

declare(strict_types=1);

namespace App\Application\Command;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Validated request DTO for the send-report use case.
 */
final class SendChargeReportRequest
{
    public function __construct(
        public readonly string $merchantId,
        public readonly DateTimeImmutable $from,
        public readonly DateTimeImmutable $to,
    ) {
    }

    /**
     *  @throws InvalidArgumentException
     */
    public static function fromStrings(string $merchantId, string $from, string $to): self
    {
        if (trim($merchantId) === '') {
            throw new InvalidArgumentException('merchant_id cannot be empty.');
        }

        if (!self::isValidDate($from)) {
            throw new InvalidArgumentException(sprintf('Invalid "from" date "%s". Expected YYYY-MM-DD.', $from));
        }

        if (!self::isValidDate($to)) {
            throw new InvalidArgumentException(sprintf('Invalid "to" date "%s". Expected YYYY-MM-DD.', $to));
        }

        $fromDt = new DateTimeImmutable($from . ' 00:00:00');
        $toDt = new DateTimeImmutable($to . ' 23:59:59');

        if ($fromDt > $toDt) {
            throw new InvalidArgumentException('"from" date must be before or equal to "to" date.');
        }

        return new self($merchantId, $fromDt, $toDt);
    }

    private static function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}