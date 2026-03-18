<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function add(string $method, string $path, callable $handler): void
    {
        $this->routes[strtoupper($method)][$path] = $handler;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        // Exact match first.
        if (isset($this->routes[$method][$path])) {
            return ($this->routes[$method][$path])($request);
        }

        // Pattern match for routes with placeholders like /charges/{id}.
        foreach ($this->routes[$method] ?? [] as $pattern => $handler) {
            $params = $this->matchPattern($pattern, $path);

            if ($params !== null) {
                return $handler($request, $params);
            }
        }

        return Response::notFound(sprintf('Route "%s %s" not found.', $method, $path));
    }

    /**
     * Match a route pattern against a path, returning named parameters or null.
     *
     * @return array<string,string>|null
     */
    private function matchPattern(string $pattern, string $path): ?array
    {
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';

        if (preg_match($regex, $path, $matches) !== 1) {
            return null;
        }

        $params = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $params[$key] = $value;
            }
        }

        return $params;
    }
}
