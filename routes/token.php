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

use Ramsey\Uuid\Uuid;
use Firebase\JWT\JWT;
use Tuupola\Base62;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Slim\Routing\RouteCollectorProxy;

$app->group("/token", function (RouteCollectorProxy $group) { 
    /* Allow preflight requests */
    $group->options('', function (Request $request, Response $response): Response {
        return $response;
    });

    $group->post('', function (Request $request, Response $response, $arguments) {
        $requested_scopes = $request->getParsedBody() ?: [];

        $valid_scopes = [
            "todo.create",
            "todo.read",
            "todo.update",
            "todo.delete",
            "todo.list",
            "todo.all"
        ];

        $scopes = array_filter($requested_scopes, function ($needle) use ($valid_scopes) {
            return in_array($needle, $valid_scopes);
        });

        $now = new DateTime();
        $future = new DateTime("now +2 hours");
        $server = $request->getServerParams();

        $jti = (new Base62)->encode(random_bytes(16));

        $payload = [
            "iat" => $now->getTimeStamp(),
            "exp" => $future->getTimeStamp(),
            "jti" => $jti,
            "sub" => $server["PHP_AUTH_USER"],
            "scope" => $scopes
        ];

        $secret = $_ENV["JWT_SECRET"];
        $token = JWT::encode($payload, $secret, "HS256");

        $data["token"] = $token;
        $data["expires"] = $future->getTimeStamp();

        $body = $response->getBody();
        $body->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json");
    });
});


/* This is just for debugging, not usefull in real life. */
$app->group("/dump", function (RouteCollectorProxy $group) {  
    $group->get('', function ($request, $response, $arguments) {
        print_r($this->token);
    });

    $group->post('', function ($request, $response, $arguments) {
        print_r($this->token);
    });
});


/* This is just for debugging, not usefull in real life. */
$app->group("/info", function (RouteCollectorProxy $group) { 
    $group->get('', function ($request, $response, $arguments) {
        phpinfo();
    });
});
