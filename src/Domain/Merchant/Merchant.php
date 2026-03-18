<?php

declare(strict_types=1);

namespace App\Domain\Merchant;

use App\Domain\Shared\ValueObject\MerchantId;

final class Merchant
{
    public function __construct(
        private readonly MerchantId $id,
        private readonly string $name,
        private readonly string $email,
        private readonly string $apiKey,
        private readonly PspType $pspType
    ) {
    }

    public function getId(): MerchantId
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getPspType(): PspType
    {
        return $this->pspType;
    }
}