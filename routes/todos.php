<?php
declare(strict_types=1);
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

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Skeleton\Application\Response\{
    NotFoundResponse,
    ForbiddenResponse,
    PreconditionFailedResponse,
    PreconditionRequiredResponse
};

use Skeleton\Application\Todo\{
    CreateTodoCommand,
    ReadTodoQuery,
    DeleteTodoCommand,
    LatestTodoQuery,
    ReadTodoCommand,
    ReplaceTodoCommand,
    UpdateTodoCommand,
    ReadTodoCollectionQuery,
    TodoNotFoundException
};

use Skeleton\Domain\{
    TodoUid
};

use Slim\Routing\RouteCollectorProxy;


$app->group("/todos", function (RouteCollectorProxy $group) use ($cacheProvider) {
    /* Allow preflight requests */
    $group->options('', function (Request $request, Response $response): Response {
        return $response;
    });

    $group->get('', function (Request $request, Response  $response, array $arguments) use ($cacheProvider): Response {
        /* Check if token has needed scope. */
        if (false === $this->get('token')->hasScope(["todo.all", "todo.list"])) {
            return new ForbiddenResponse("Token not allowed to list todos", 403);
        }

        /* Add Last-Modified and ETag headers to response when atleast one todo exists. */
        try {
            $query = new LatestTodoQuery;
            $first = $this->get('commandBus')->handle($query);

            $response = $cacheProvider->withEtag($response, $first->etag());
            $response = $cacheProvider->withLastModified($response, $first->timestamp());
        } catch (TodoNotFoundException $exception) {
        }

        /* Serialize the response. */
        $query = new ReadTodoCollectionQuery;
        $todos = $this->get("commandBus")->handle($query);
        $data = $this->get("transformTodoCollectionService")->execute($todos);

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return $response;
    });


    $group->post('', function (Request $request, Response  $response, array $arguments) use ($cacheProvider): Response {
        /* Check if token has needed scope. */
        if (false === $this->get("token")->hasScope(["todo.all", "todo.create"])) {
            return new ForbiddenResponse("Token not allowed to create todos", 403);
        }
    
        $data = $request->getParsedBody();
        $uid = $this->get("todoRepository")->nextIdentity();
    
        $command = new CreateTodoCommand(
            $uid,
            $data["title"],
            $data["order"]
        );
        $this->get("commandBus")->handle($command);
    
        $query = new ReadTodoQuery($uid);
        $todo = $this->get("commandBus")->handle($query);
    
        /* Add Last-Modified and ETag headers to response. */
        $response = $cacheProvider->withEtag($response, $todo->etag());
        $response = $cacheProvider->withLastModified($response, $todo->timestamp());
    
        /* Serialize the response. */
        $data = $this->get("transformTodoService")->execute($todo);
    
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    
        return $response->withStatus(201)
            ->withHeader("Content-Type", "application/json")
            ->withHeader("Content-Location", $data["data"]["links"]["self"]);
            
    });
});

$app->group("/todos/{uid}", function (RouteCollectorProxy $group) use ($cacheProvider) { 
    /* Allow preflight requests */
    $group->options('', function (Request $request, Response $response): Response {
        return $response;
    });

    $group->get('', function (Request $request, Response  $response, array $arguments) use ($cacheProvider): Response {
        /* Check if token has needed scope. */
        if (false === $this->get("token")->hasScope(["todo.all", "todo.read"])) {
            return new ForbiddenResponse("Token not allowed to read todos", 403);
        }

        $uid = new TodoUid($arguments["uid"]);

        /* Load existing todo using provided uid. */
        try {
            $query = new ReadTodoQuery($uid);
            $todo = $this->get("commandBus")->handle($query);
        } catch (TodoNotFoundException $exception) {
            return new NotFoundResponse("Todo not found", 404);
        }

        /* Add Last-Modified and ETag headers to response. */
        $response = $cacheProvider->withEtag($response, $todo->etag());
        $response = $cacheProvider->withLastModified($response, $todo->timestamp());

        /* Serialize the response. */
        $data = $this->get("transformTodoService")->execute($todo);

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json");   
    });

    $group->map(["PUT", "PATCH"], '', function (Request $request, Response  $response, array $arguments) use ($cacheProvider): Response {
        /* Check if token has needed scope. */
        if (false === $this->get("token")->hasScope(["todo.all", "todo.update"])) {
            return new ForbiddenResponse("Token not allowed to update todos", 403);
        }

        $uid = new TodoUid($arguments["uid"]);

        /* Load existing todo using provided uid. */
        try {
            $query = new ReadTodoQuery($uid);
            $todo = $this->get("commandBus")->handle($query);
        } catch (TodoNotFoundException $exception) {
            return new NotFoundResponse("Todo not found", 404);
        }

        /* PATCH requires If-Unmodified-Since or If-Match request header to be present. */
        if (($request->hasHeader("If-Unmodified-Since") === false) && ($request->hasHeader("If-Match") === false)) {
            $method = strtoupper($request->getMethod());
            return new PreconditionRequiredResponse("{$method} request is required to be conditional", 428);
        }

        if ($request->hasHeader("If-Match")) {
            if ($request->getHeader("If-Match")[0] != $todo->etag()) {
                return new PreconditionFailedResponse("Todo has already been modified", 412); 
            }
        }
        if ($request->hasHeader("If-Unmodified-Since")) {
            /* TODO: Handle in-correct header */
            if (strtotime($request->getHeader("If-Unmodified-Since")[0]) <= $todo->timestamp()) {
                return new PreconditionFailedResponse("Todo has already been modified", 412); 
            }
        }
        // /* If-Unmodified-Since and If-Match request header handling. If in the meanwhile  */
        // /* someone has modified the todo respond with 412 Precondition Failed. */
        // if (false === $this->cache->hasCurrentState($request, $todo->etag(), $todo->timestamp())) {
        //     return new PreconditionFailedResponse("Todo has already been modified", 412);
        // }

        $data = $request->getParsedBody();

        /* PUT request assumes full representation. PATCH allows partial data. */
        if ("PUT" === strtoupper($request->getMethod())) {
            $command = new ReplaceTodoCommand(
                $uid,
                $data["title"],
                $data["order"],
                $data["completed"]
            );
        } else {
            $command = new UpdateTodoCommand(
                $uid,
                $data["title"] ?? $todo->title(),
                $data["order"] ?? $todo->order(),
                $data["completed"] ?? $todo->isCompleted()
            );
        }
        $this->get("commandBus")->handle($command);

        $query = new ReadTodoQuery($uid);
        $todo = $this->get("commandBus")->handle($query);

        /* Add Last-Modified and ETag headers to response. */
        $response = $cacheProvider->withEtag($response, $todo->etag());
        $response = $cacheProvider->withLastModified($response, $todo->timestamp());

        $data = $this->get("transformTodoService")->execute($todo);

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

        return $response->withStatus(200)
            ->withHeader("Content-Type", "application/json");         
    });

    $group->delete('', function (Request $request, Response  $response, array $arguments) use ($cacheProvider): Response {
        /* Check if token has needed scope. */
        if (false === $this->get("token")->hasScope(["todo.all", "todo.delete"])) {
            return new ForbiddenResponse("Token not allowed to delete todos", 403);
        }
    
        $uid = new TodoUid($arguments["uid"]);
    
        try {
            $command = new DeleteTodoCommand($uid);
            $todo = $this->get("commandBus")->handle($command);
        } catch (TodoNotFoundException $exception) {
            return new NotFoundResponse("Todo not found", 404);
        }
    
        return $response->withStatus(204);
    });
});
