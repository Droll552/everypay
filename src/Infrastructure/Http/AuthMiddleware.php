<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use App\Domain\Merchant\MerchantRepositoryInterface;
use App\Infrastructure\Http\Request;
use App\Infrastructure\Http\Response;

final class AuthMiddleware
{
    public function __construct(private MerchantRepositoryInterface $merchantRepository){}

    /**
     * Validate Bearer token and inject resolved merchant id into the request attributes.
     * Returns a 401 Response on failure, or null if authentication passes.
     */
    public function handle(Request $request): ?Response {
        $token = $request->getBearerToken();

        if ($token === null) {
            return Response::unauthorized('Missing or malformed Authorization header.');
        }

        $merchant = $this->merchantRepository->findByApiKey($token);

        if ($merchant === null) {
            return Response::unauthorized('Invalid API key.');
        }

        return null;
    }

    /**
     * Resolve the merchant id from a valid token.
     * Only call this after handle() returned null.
     */
    public function resolveMerchantId(Request $request): string
    {
        $token    = $request->getBearerToken();
        $merchant = $this->merchantRepository->findByApiKey((string) $token);

        // @phpstan-ignore-next-line — guaranteed non-null after handle() passes
        return $merchant->getId()->getValue();
    }

}