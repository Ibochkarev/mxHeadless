<?php

declare(strict_types=1);

namespace MxHeadless\Http;

use Psr\Http\Message\ResponseInterface;

final class ProblemDetails
{
    /**
     * @param array<string, mixed> $extra
     */
    public static function create(
        string $type,
        string $title,
        int $status,
        string $detail = '',
        string $instance = '',
        array $extra = [],
    ): array {
        $problem = [
            'type' => $type,
            'title' => $title,
            'status' => $status,
        ];

        if ($detail !== '') {
            $problem['detail'] = $detail;
        }

        if ($instance !== '') {
            $problem['instance'] = $instance;
        }

        return array_merge($problem, $extra);
    }

    public static function response(
        string $type,
        string $title,
        int $status,
        string $detail = '',
        string $instance = '',
        array $extra = [],
    ): ResponseInterface {
        return Psr7Factory::json(
            self::create($type, $title, $status, $detail, $instance, $extra),
            $status,
            ['Content-Type' => 'application/problem+json; charset=utf-8'],
        );
    }
}
