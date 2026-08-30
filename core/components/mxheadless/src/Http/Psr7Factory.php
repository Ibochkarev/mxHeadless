<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

final class Psr7Factory
{
    private static ?Psr17Factory $factory = null;

    public static function create(): Psr17Factory
    {
        return self::$factory ??= new Psr17Factory();
    }

    /**
     * @param array<string, string> $headers
     */
    public static function createResponse(int $status = 200, array $headers = [], ?string $body = null): ResponseInterface
    {
        $factory = self::create();
        $response = $factory->createResponse($status);
        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        if ($body !== null) {
            $response = $response->withBody($factory->createStream($body));
        }

        return $response;
    }

    /**
     * @param array<string, string> $headers
     */
    public static function json(mixed $data, int $status = 200, array $headers = []): ResponseInterface
    {
        $headers['Content-Type'] = $headers['Content-Type'] ?? 'application/json; charset=utf-8';

        return self::createResponse(
            $status,
            $headers,
            json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        );
    }
}
