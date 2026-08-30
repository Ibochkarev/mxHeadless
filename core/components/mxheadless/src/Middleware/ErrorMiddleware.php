<?php

declare(strict_types=1);

namespace MxHeadless\Middleware;

use MODX\Revolution\modX;
use MxHeadless\Exception\HttpException;
use MxHeadless\Http\ProblemDetails;
use MxHeadless\Http\RequestContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class ErrorMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (HttpException $e) {
            return $e->toResponse(RequestContext::path($request));
        } catch (Throwable $e) {
            $this->modx->log(
                modX::LOG_LEVEL_ERROR,
                sprintf(
                    '[mxHeadless] %s in %s:%d',
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine(),
                ),
            );

            $debug = (bool) $this->modx->getOption('mxheadless_debug', null, false);
            $extra = $debug ? [
                'debug' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                ],
            ] : [];

            return ProblemDetails::response(
                'https://mxheadless.dev/problems/internal-error',
                'Internal Server Error',
                500,
                'An unexpected error occurred',
                RequestContext::path($request),
                array_merge(['code' => 'internal_error'], $extra),
            );
        }
    }
}
