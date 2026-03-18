<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Merchant\Merchant;
use App\Domain\Merchant\MerchantRepositoryInterface;
use App\Domain\Merchant\PspType;
use App\Domain\Shared\MerchantId;
use PDO;

final class SqliteMerchantRepository implements MerchantRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function findById(MerchantId $id): ?Merchant
    {
        $stmt = $this->pdo->prepare('SELECT * FROM merchants WHERE id = :id');
        $stmt->execute([':id' => $id->getValue()]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $this->hydrate($row) : null;
    }

    public function findByApiKey(string $apiKey): ?Merchant
    {
        $stmt = $this->pdo->prepare('SELECT * FROM merchants WHERE api_key = :api_key');
        $stmt->execute([':api_key' => $apiKey]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $this->hydrate($row) : null;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function hydrate(array $row): Merchant
    {
        return new Merchant(
            id: MerchantId::fromString($row['id']),
            name: $row['name'],
            email: $row['email'],
            apiKey: $row['api_key'],
            pspType: PspType::from($row['psp_type']),
        );
    }
}
