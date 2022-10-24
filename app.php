<?php

/*
 * This file is part of the Slim API skeleton package
 *
 * Copyright (c) 2016-2017 Mika Tuupola
 *
 * Licensed under the MIT license:
 *   http://www.opensource.org/licenses/mit-license.php
 *
 * Project home:
 *   https://github.com/tuupola/slim-api-skeleton
 *
 */

date_default_timezone_set("UTC");
error_reporting(E_ALL);
ini_set("display_errors", 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

use Slim\Factory\AppFactory;
use Slim\Middleware\ContentLengthMiddleware;
use Slim\Routing\RouteContext;
use Slim\Exception\HttpNotFoundException;

require __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$container = new \DI\Container();
AppFactory::setContainer($container);

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

// Add any middleware which may modify the response body before adding the ContentLengthMiddleware
$contentLengthMiddleware = new ContentLengthMiddleware();
$app->add($contentLengthMiddleware);

// Create the cache provider.
$cacheProvider = new \Slim\HttpCache\CacheProvider();
// Register the http cache middleware.
$app->add(new \Slim\HttpCache\Cache('public', 86400));

require __DIR__ . "/config/dependencies.php";
require __DIR__ . "/config/handlers.php";
require __DIR__ . "/config/middleware.php";

// This middleware will append the response header Access-Control-Allow-Methods with all allowed methods
$app->add(function (Request $request, RequestHandler $handler): Response {
    $routeContext = RouteContext::fromRequest($request);
    $routingResults = $routeContext->getRoutingResults();
    $methods = $routingResults->getAllowedMethods();
    $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');

    $response = $handler->handle($request);

    $response = $response->withHeader('Access-Control-Allow-Origin', '*');
    $response = $response->withHeader('Access-Control-Allow-Methods', implode(',', $methods));
    $response = $response->withHeader('Access-Control-Allow-Headers', $requestHeaders);

    // Optional: Allow Ajax CORS requests with Authorization header
    // $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');

    return $response;
});

// The RoutingMiddleware should be added after our CORS middleware so routing is performed first
$app->addRoutingMiddleware();

$app->get("/", function ($request, $response, $arguments) {
    print "Here be dragons";
});

/* TODO: Make dynamic instance of OpenAPI */
$app->get("/swagger.json", function ($request, $response, $arguments) {
    $data = file_get_contents(__DIR__ . '/swagger.json');
    $obj = json_decode($data);
    $payload = json_encode($obj, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    $response->getBody()->write($payload);
    return $response
          ->withHeader('Content-Type', 'application/json');
});

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

require __DIR__ . "/routes/token.php";
require __DIR__ . "/routes/todos.php";


$app->run();
