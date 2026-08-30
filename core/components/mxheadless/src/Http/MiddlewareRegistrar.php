<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use Psr\Http\Server\MiddlewareInterface;

final class MiddlewareRegistrar
{
    /** @var list<MiddlewareInterface> */
    private array $stack;

    /**
     * @param list<MiddlewareInterface> $stack Outermost middleware first (same order as MiddlewarePipeline).
     */
    public function __construct(array $stack)
    {
        $this->stack = $stack;
    }

    public function prepend(MiddlewareInterface $middleware): void
    {
        array_unshift($this->stack, $middleware);
    }

    public function append(MiddlewareInterface $middleware): void
    {
        $this->stack[] = $middleware;
    }

    /**
     * @return list<MiddlewareInterface>
     */
    public function all(): array
    {
        return $this->stack;
    }
}
