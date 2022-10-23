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

use Skeleton\Infrastructure\Slim\Handler\ApiErrorHandler;
use Skeleton\Infrastructure\Slim\Handler\NotFoundHandler;

$container = $app->getContainer();

$container->set("errorHandler", function(\Psr\Container\ContainerInterface $container) {
    return new ApiErrorHandler($container["logger"]);
});

$container->set("phpErrorHandler", function(\Psr\Container\ContainerInterface $container) {
    return $container["errorHandler"];
});

$container->set("notFoundHandler", function(\Psr\Container\ContainerInterface $container) {
    return new NotFoundHandler;
});
