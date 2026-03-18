<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

final class Response
{
    /** @param array<string,mixed> $data */
    public function __construct(
        private readonly int $statusCode,
        private readonly array $data,
    ) {
    }

    /** @param array<string,mixed> $data */
    public static function json(int $statusCode, array $data): self
    {
        return new self($statusCode, $data);
    }

    public static function ok(array $data): self
    {
        return new self(200, $data);
    }

    public static function created(array $data): self
    {
        return new self(201, $data);
    }

    public static function badRequest(string $message): self
    {
        return new self(400, ['error' => $message]);
    }

    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return new self(401, ['error' => $message]);
    }

    public static function notFound(string $message = 'Not found'): self
    {
        return new self(404, ['error' => $message]);
    }

    public static function internalError(string $message = 'Internal server error'): self
    {
        return new self(500, ['error' => $message]);
    }

    public function send(): void
    {
        http_response_code($this->statusCode);
        header('Content-Type: application/json');

        echo json_encode($this->data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<string,mixed> */
    public function getData(): array
    {
        return $this->data;
    }
}
