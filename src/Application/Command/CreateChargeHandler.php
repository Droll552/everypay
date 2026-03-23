<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Charge\Charge;
use App\Domain\Charge\ChargeId;
use App\Domain\Charge\ChargeRepositoryInterface;
use App\Domain\Charge\ChargeStatus;
use App\Domain\Charge\Money;
use App\Domain\Merchant\MerchantRepositoryInterface;
use App\Domain\Shared\MerchantId;
use App\Infrastructure\PSP\PspRegistry;
use DateTimeImmutable;
use RuntimeException;
use Ramsey\Uuid\Uuid;

final class CreateChargeHandler
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepository,
        private readonly ChargeRepositoryInterface $chargeRepository,
        private readonly PspRegistry $pspRegistry
    ) {
    }

    public function handle(CreateChargeCommand $command): CreateChargeResponse
    {
        $merchantId = MerchantId::fromString($command->merchantId);
        $merchant = $this->merchantRepository->findById($merchantId);

        if ($merchant === null) {
            throw new RuntimeException(sprintf('Merchant "%s" not found.', $command->merchantId));
        }

        $money = new Money($command->amount, $command->currency);
        $psp = $this->pspRegistry->get($merchant->getPspType());

        $pspResult = $psp->charge($money, $command->credentials);

        $chargeId = ChargeId::fromString(Uuid::uuid4()->toString());
        $status = $pspResult->isSuccess() ? ChargeStatus::Successful : ChargeStatus::Failed;

        $charge = Charge::create(
            $chargeId,
            $merchantId,
            $money,
            $status,
            $pspResult->getReference(),
            new DateTimeImmutable()
        );

        $this->chargeRepository->save($charge);

        return new CreateChargeResponse(
            chargeId: $chargeId->getValue(),
            status: $status->value,
            pspReference: $pspResult->getReference(),
            errorMessage: $pspResult->getErrorMessage()
        );

    }
}