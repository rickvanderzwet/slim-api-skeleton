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
use Slim\Factory\AppFactory;
use Slim\Middleware\ContentLengthMiddleware;

require __DIR__ . "/vendor/autoload.php";

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$container = new \DI\Container();
AppFactory::setContainer($container);

$app = AppFactory::create();

// Add any middleware which may modify the response body before adding the ContentLengthMiddleware
$contentLengthMiddleware = new ContentLengthMiddleware();
$app->add($contentLengthMiddleware);

require __DIR__ . "/config/dependencies.php";
require __DIR__ . "/config/handlers.php";
require __DIR__ . "/config/middleware.php";

$errorMiddleware = $app->addErrorMiddleware(true, true, true);


$app->get("/", function ($request, $response, $arguments) {
    print "Here be dragons";
});



require __DIR__ . "/routes/token.php";
require __DIR__ . "/routes/todos.php";

$app->run();
