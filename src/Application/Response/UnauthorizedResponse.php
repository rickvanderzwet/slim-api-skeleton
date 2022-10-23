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

namespace Skeleton\Application\Response;

use Crell\ApiProblem\ApiProblem;
use Slim\Psr7\Response;

class UnauthorizedResponse extends Response
{
    public function __construct($message, $status = 404)
    {
        parent::__construct($status);
        $this->withHeader("Content-type", "application/problem+json");

        $problem = new ApiProblem(
            $message,
            "http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html"
        );
        $problem->setStatus($status);
        $this->getBody()->write($problem->asJson(true));
    }
}