<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Charge\Charge;
use App\Domain\Charge\ChargeId;
use App\Domain\Charge\ChargeRepositoryInterface;
use App\Domain\Charge\ChargeStatus;
use App\Domain\Charge\Money;
use App\Domain\Shared\MerchantId;
use DateTimeImmutable;
use PDO;
use RuntimeException;

final class SqliteChargeRepository implements ChargeRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(Charge $charge): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO charges (id, merchant_id, amount, currency, status, psp_reference, created_at)
             VALUES (:id, :merchant_id, :amount, :currency, :status, :psp_reference, :created_at)
             ON CONFLICT(id) DO UPDATE SET
               status        = excluded.status,
               psp_reference = excluded.psp_reference'
        );

        $stmt->execute([
            ':id' => $charge->getId()->getValue(),
            ':merchant_id' => $charge->getMerchantId()->getValue(),
            ':amount' => $charge->getAmount()->getAmount(),
            ':currency' => $charge->getAmount()->getCurrency(),
            ':status' => $charge->getStatus()->value,
            ':psp_reference' => $charge->getPspReference(),
            ':created_at' => $charge->getCreatedAt()->format('Y-m-d H:i:s'),
        ]);
    }

    public function findById(ChargeId $id): ?Charge
    {
        $stmt = $this->pdo->prepare('SELECT * FROM charges WHERE id = :id');
        $stmt->execute([':id' => $id->getValue()]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findByMerchantAndPeriod(
        MerchantId $merchantId,
        DateTimeImmutable $from,
        DateTimeImmutable $to,
    ): array {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM charges
             WHERE merchant_id = :merchant_id
               AND created_at >= :from
               AND created_at <= :to
             ORDER BY created_at ASC'
        );

        $stmt->execute([
            ':merchant_id' => $merchantId->getValue(),
            ':from' => $from->format('Y-m-d H:i:s'),
            ':to' => $to->format('Y-m-d H:i:s'),
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function hydrate(array $row): Charge
    {
        $status = ChargeStatus::tryFrom($row['status']);

        if ($status === null) {
            throw new RuntimeException(sprintf('Unknown charge status "%s".', $row['status']));
        }

        return Charge::create(
            id: ChargeId::fromString($row['id']),
            merchantId: MerchantId::fromString($row['merchant_id']),
            amount: new Money((int) $row['amount'], $row['currency']),
            status: $status,
            pspReference: $row['psp_reference'],
            createdAt: new DateTimeImmutable($row['created_at']),
        );
    }
}
