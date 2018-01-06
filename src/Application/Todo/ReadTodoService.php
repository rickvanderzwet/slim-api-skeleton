<?php
declare(strict_types=1);

namespace Skeleton\Application\Todo;

use RuntimeException;
use Skeleton\Domain\Todo;
use Skeleton\Domain\TodoRepository;

class ReadTodoService
{
    private $repository;

    public function __construct(TodoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function execute(array $request): Todo
    {
        $todo = $this->repository->get($request["uid"]);
        if (null === $todo) {
            throw new RuntimeException("Todo {$request['uid']} does not exist.");
        }
        return $todo;
    }
}
