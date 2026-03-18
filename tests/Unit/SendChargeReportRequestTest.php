<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Application\Command\SendChargeReportRequest;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SendChargeReportRequestTest extends TestCase
{
    public function test_creates_request_with_valid_input(): void
    {
        $dto = SendChargeReportRequest::fromStrings('merchant-1', '2024-01-01', '2024-01-31');

        $this->assertSame('merchant-1', $dto->merchantId);
        $this->assertSame('2024-01-01 00:00:00', $dto->from->format('Y-m-d H:i:s'));
        $this->assertSame('2024-01-31 23:59:59', $dto->to->format('Y-m-d H:i:s'));
    }

    public function test_throws_when_merchant_id_is_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SendChargeReportRequest::fromStrings('', '2024-01-01', '2024-01-31');
    }

    public function test_throws_when_from_date_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SendChargeReportRequest::fromStrings('merchant-1', 'not-a-date', '2024-01-31');
    }

    public function test_throws_when_to_date_is_invalid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        SendChargeReportRequest::fromStrings('merchant-1', '2024-01-01', '99-99-99');
    }

    public function test_throws_when_from_is_after_to(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/"from" date must be before/');
        SendChargeReportRequest::fromStrings('merchant-1', '2024-02-01', '2024-01-01');
    }
}
