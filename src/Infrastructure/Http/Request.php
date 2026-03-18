<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

final class Request
{
    /** @var array<string,mixed> */
    private array $body;

    /** @var array<string,string> */
    private array $headers;

    /**
     * @param array<string,string> $headers
     * @param array<string,mixed>  $body
     * @param array<string,string> $queryParams
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        array $headers,
        array $body,
        private readonly array $queryParams = [],
    ) {
        $this->headers = array_change_key_case($headers, CASE_LOWER);
        $this->body = $body;
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        $body = [];
        if (in_array($method, ['POST', 'PUT', 'PATCH'], true)) {
            $raw = file_get_contents('php://input');
            $body = $raw !== false ? (json_decode($raw, true) ?? []) : [];
        }

        return new self($method, $path, $headers, $body, $_GET);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    /**
     * @return array<string,mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @return mixed
     */
    public function getBodyParam(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $default;
    }

    public function getBearerToken(): ?string
    {
        $auth = $this->getHeader('authorization');

        if ($auth === null || !str_starts_with($auth, 'Bearer ')) {
            return null;
        }

        return substr($auth, 7);
    }
}