<?php

declare(strict_types= 1);

namespace App\Infrastructure\Http;

use App\Application\Command\CreateChargeCommand;
use App\Application\Command\CreateChargeHandler;
use App\Application\Command\CreateChargeRequest;
use App\Infrastructure\Http\AuthMiddleware;
use App\Infrastructure\Http\Request;
use InvalidArgumentException;
use Throwable;

final class ChargeHandler
{
    public function __construct(
        private readonly CreateChargeHandler $handler,
        private readonly AuthMiddleware $authMiddleware,
    ){}

    public function handle(Request $request): Response
    {
        $authError = $this->authMiddleware->handle($request);
        if ($authError !== null) {
            return $authError;
        }

        $merchantId = $this->authMiddleware->resolveMerchantId($request);

        try {
            // Validate and parse the incoming payload via the request DTO.
            $dto = CreateChargeRequest::fromArray($request->getBody());
        } catch (InvalidArgumentException $e) {
            return Response::badRequest($e->getMessage());
        }

        try {
            $command = new CreateChargeCommand(
                merchantId:  $merchantId,
                amount:      $dto->amount,
                currency:    $dto->currency,
                credentials: $dto->credentials,
            );

            $response = $this->handler->handle($command);
        } catch (InvalidArgumentException $e) {
            return Response::badRequest($e->getMessage());
        } catch (Throwable $e) {
            return Response::internalError($e->getMessage());
        }

        return Response::created($response->toArray());
    }
}