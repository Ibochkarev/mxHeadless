<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class MiddlewarePipeline implements RequestHandlerInterface
{
    /** @var list<MiddlewareInterface> */
    private array $middleware;

    /**
     * @param list<MiddlewareInterface> $middleware
     */
    public function __construct(
        private readonly RequestHandlerInterface $handler,
        array $middleware,
    ) {
        $this->middleware = array_reverse($middleware);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $handler = $this->handler;
        foreach ($this->middleware as $mw) {
            $next = $handler;
            $handler = new class ($mw, $next) implements RequestHandlerInterface {
                public function __construct(
                    private readonly MiddlewareInterface $middleware,
                    private readonly RequestHandlerInterface $next,
                ) {
                }

                public function handle(ServerRequestInterface $request): ResponseInterface
                {
                    return $this->middleware->process($request, $this->next);
                }
            };
        }

        return $handler->handle($request);
    }
}
