<?php

declare(strict_types=1);

namespace App\Domain\Merchant;

use App\Domain\Shared\MerchantId;

interface MerchantRepositoryInterface
{
    public function findById(MerchantId $id): ?Merchant;

    public function findByApiKey(string $apiKey): ?Merchant;
}