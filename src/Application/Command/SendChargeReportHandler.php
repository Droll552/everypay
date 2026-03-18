<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Application\Query\GetChargesForPeriodHandler;
use App\Application\Query\GetChargesForPeriodQuery;
use App\Application\ChargeReportBuilder;
use App\Domain\Merchant\MerchantRepositoryInterface;
use App\Domain\Shared\MailerInterface;
use App\Domain\Shared\MerchantId;
use RuntimeException;

final class SendChargeReportHandler
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly GetChargesForPeriodHandler $queryHandler,
        private readonly ChargeReportBuilder $reportBuilder,
        private readonly MailerInterface $mailer,
    ) {}

    public function handle(SendChargeReportCommand $command): void
    {
        $merchantId = MerchantId::fromString($command->merchantId);
        $merchant   = $this->merchantRepository->findById($merchantId);

        if ($merchant === null) {
            throw new RuntimeException(sprintf('Merchant "%s" not found.', $command->merchantId));
        }

        $charges = $this->queryHandler->handle(
            new GetChargesForPeriodQuery($command->merchantId, $command->from, $command->to)
        );

        $body = $this->reportBuilder->build(
            merchantName: $merchant->getName(),
            from:         $command->from->format('Y-m-d'),
            to:           $command->to->format('Y-m-d'),
            charges:      $charges,
        );

        $this->mailer->send(
            to:      $merchant->getEmail(),
            subject: sprintf(
                'Charge Report for %s (%s – %s)',
                $merchant->getName(),
                $command->from->format('Y-m-d'),
                $command->to->format('Y-m-d'),
            ),
            body: $body,
        );
    }
}
