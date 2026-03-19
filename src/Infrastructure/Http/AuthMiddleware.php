<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Merchant\MerchantRepositoryInterface;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;
use App\Domain\Merchant\Merchant;

final class AuthMiddleware
{
    /**
     * Cached merchant from the last successful handle() call.
     * Avoids a second DB round-trip when resolveMerchantId() is called immediately after.
     */
    private ?Merchant $resolvedMerchant = null;
    public function __construct(private MerchantRepositoryInterface $merchantRepository)
    {
    }

    /**
     * Validate Bearer token and inject resolved merchant id into the request attributes.
     * Returns a 401 Response on failure, or null if authentication passes.
     */
    public function handle(Request $request): ?Response
    {
        $this->resolvedMerchant = null;

        $token = $request->getBearerToken();

        if ($token === null) {
            return Response::unauthorized('Missing or malformed Authorization header.');
        }

        $merchant = $this->merchantRepository->findByApiKey($token);

        if ($merchant === null) {
            return Response::unauthorized('Invalid API key.');
        }

        $this->resolvedMerchant = $merchant;

        return null;
    }

    /**
     * Resolve the merchant id from a valid token.
     * Only call this after handle() returned null.
     */
    public function resolveMerchantId(Request $request): string
    {
        if ($this->resolvedMerchant === null) {
            throw new \LogicException('resolveMerchantId() called before a successful handle().');
        }

        return $this->resolvedMerchant->getId()->getValue();

    }

}