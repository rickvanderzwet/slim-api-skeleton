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

use Skeleton\Domain\Token;
use Skeleton\Middleware\JsonBodyParserMiddleware;
use Crell\ApiProblem\ApiProblem;
use Gofabian\Negotiation\NegotiationMiddleware;
use Tuupola\Middleware\JwtAuthentication;
use Tuupola\Middleware\HttpBasicAuthentication;
use Tuupola\Middleware\CorsMiddleware;
use Skeleton\Application\Response\UnauthorizedResponse;
use Slim\Factory\AppFactory;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

function unauthorizedResponse($response, $message, $status = 401)
{
    $problem = new ApiProblem($message, "about:blank");
    $problem->setStatus($status);

    $body = $response->getBody();
    $body->write($problem->asJson(true));

    return $response
        ->withHeader("Content-type", "application/problem+json")
        ->withStatus($status);
}

$container = $app->getContainer();

$container->set("HttpBasicAuthentication", function (\Psr\Container\ContainerInterface $container) {
    return new HttpBasicAuthentication([
        "path" => "/token",
        "relaxed" => ["192.168.50.52", "127.0.0.1", "localhost"],
        "error" => function ($response, $arguments) {
            return unauthorizedResponse($response, $arguments["message"], 401);
        },
        "users" => [
            "test" => "test"
        ]
    ]);
});

$container->set("token", function (\Psr\Container\ContainerInterface $container) {
    error_log("Function token called");
    return new Token([]);
});

$container->set("JwtAuthentication", function (\Psr\Container\ContainerInterface $container) {
    return new JwtAuthentication([
        "path" => "/",
        "ignore" => ["/token", "/info"],
        "secret" => $_ENV["JWT_SECRET"],
        "logger" => $container->get("logger"),
        "attribute" => false,
        "relaxed" => ["192.168.50.52", "127.0.0.1", "localhost"],
        "error" => function ($response, $arguments) {
            return unauthorizedResponse($response, $arguments["message"], 401);
        },
        "before" => function ($request, $arguments) use ($container) {
            $container->get("token")->populate($arguments["decoded"]);
        }
    ]);
});

$container->set("CorsMiddleware", function (\Psr\Container\ContainerInterface $container) {
    return new CorsMiddleware([
        "logger" => $container->get("logger"),
        "origin" => ["*"],
        "methods" => ["GET", "POST", "PUT", "PATCH", "DELETE"],
        "headers.allow" => ["Authorization", "If-Match", "If-Unmodified-Since"],
        "headers.expose" => ["Authorization", "Etag"],
        "credentials" => true,
        "cache" => 60,
        "error" => function ($request, $response, $arguments) {
            return unauthorizedResponse($response, $arguments["message"], 401);
        }
    ]);
});

// Register the http cache middleware.
$app->add(new \Slim\HttpCache\Cache('public', 86400));

// Create the cache provider.
$cacheProvider = new \Slim\HttpCache\CacheProvider();

$app->add(new JsonBodyParserMiddleware());
$app->add("HttpBasicAuthentication");
$app->add("JwtAuthentication");
$app->add("CorsMiddleware");
$app->add(new NegotiationMiddleware(
    [
            "accept" => ["application/json"],
            ],
    $app->getResponseFactory(),
));