<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Domain\Charge\ChargeRepositoryInterface;
use App\Domain\Shared\MerchantId;

/**
 * Query handler: returns ChargeView DTOs for a merchant + period.
 * Used by both the report service and any future read endpoints.
 */
final class GetChargesForPeriodHandler
{
    public function __construct(
        private readonly ChargeRepositoryInterface $chargeRepository,
    ) {
    }

    /**
     * @return ChargeView[]
     */
    public function handle(GetChargesForPeriodQuery $query): array
    {
        $charges = $this->chargeRepository->findByMerchantAndPeriod(
            MerchantId::fromString($query->merchantId),
            $query->from,
            $query->to,
        );

        return array_map(
            static fn($charge) => new ChargeView(
                chargeId: $charge->getId()->getValue(),
                merchantId: $charge->getMerchantId()->getValue(),
                amount: $charge->getAmount()->getAmount(),
                currency: $charge->getAmount()->getCurrency(),
                status: $charge->getStatus()->value,
                pspReference: $charge->getPspReference(),
                createdAt: $charge->getCreatedAt()->format('Y-m-d H:i:s'),
            ),
            $charges,
        );
    }
}
