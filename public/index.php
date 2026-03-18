<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Container;
use App\Infrastructure\Http\Request;

$config = require __DIR__ . '/../config/app.php';
$container = new Container($config);

$request = Request::fromGlobals();
$router = $container->getRouter();
$response = $router->dispatch($request);

$response->send();
