<?php

declare(strict_types=1);

namespace MxHeadless\Query;

use MODX\Revolution\modX;
use MxHeadless\Exception\ValidationException;

final class QueryParser
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    public function parse(string $objectName, array $queryParams, ?string $contextHeader = null): ObjectQuery
    {
        $maxLimit = (int) $this->modx->getOption('mxheadless.max_limit', null, 100);
        $maxOffset = (int) $this->modx->getOption('mxheadless.max_offset', null, 100000);
        $maxFields = (int) $this->modx->getOption('mxheadless.max_fields', null, 50);
        $maxIncludes = (int) $this->modx->getOption('mxheadless.max_include_relations', null, 10);
        $maxIncludeDepth = (int) $this->modx->getOption('mxheadless.max_include_depth', null, 2);

        $limit = $this->resolveLimit($queryParams, $maxLimit);
        $offset = $this->resolveOffset($queryParams, $limit, $maxOffset);

        $fields = $this->parseCsv($queryParams['fields'] ?? null, $maxFields);
        $includes = $this->parseIncludes($queryParams['include'] ?? null, $maxIncludes, $maxIncludeDepth);
        $filters = $this->parseFilters($queryParams['filter'] ?? $queryParams['filters'] ?? []);
        $sorts = $this->parseSorts($queryParams['sort'] ?? null);
        $search = isset($queryParams['q']) ? trim((string) $queryParams['q']) : null;
        if ($search === '') {
            $search = null;
        }

        $context = $this->resolveContext($queryParams, $contextHeader);
        $preview = filter_var($queryParams['preview'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $includeDeleted = filter_var(
            $queryParams['include_deleted'] ?? $queryParams['includeDeleted'] ?? false,
            FILTER_VALIDATE_BOOLEAN,
        );

        return new ObjectQuery(
            $objectName,
            $filters,
            $sorts,
            new Pagination($limit, $offset),
            new FieldSelection($fields),
            new IncludeTree($includes),
            $search,
            $context,
            $preview,
            $includeDeleted,
        );
    }

    /**
     * @return list<string>
     */
    private function parseCsv(mixed $value, int $max): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', (string) $value)), static fn (string $v): bool => $v !== ''));
        if (count($parts) > $max) {
            throw new ValidationException('Too many fields requested', ['fields' => ['Maximum ' . $max . ' fields allowed']]);
        }

        return $parts;
    }

    /**
     * @return list<string>
     */
    private function parseIncludes(mixed $value, int $maxRelations, int $maxDepth): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $paths = array_values(array_filter(array_map('trim', explode(',', (string) $value)), static fn (string $v): bool => $v !== ''));
        if (count($paths) > $maxRelations) {
            throw new ValidationException('Too many includes requested', ['include' => ['Maximum ' . $maxRelations . ' relations allowed']]);
        }

        foreach ($paths as $path) {
            $depth = substr_count($path, '.') + 1;
            if ($depth > $maxDepth) {
                throw new ValidationException('Include depth exceeded', ['include' => ['Maximum depth is ' . $maxDepth]]);
            }
        }

        return $paths;
    }

    /**
     * @return list<Filter>
     */
    private function parseFilters(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $filters = [];
        foreach ($raw as $field => $operators) {
            if (!is_string($field)) {
                continue;
            }

            // Shorthand: filter[field]=value → eq
            if (!is_array($operators)) {
                $filters[] = new Filter($field, FilterOperator::Eq, $operators);
                continue;
            }

            foreach ($operators as $operator => $value) {
                if (is_int($operator)) {
                    $filters[] = new Filter($field, FilterOperator::Eq, $value);
                    continue;
                }

                if (!is_string($operator)) {
                    continue;
                }

                try {
                    $op = FilterOperator::fromString($operator);
                } catch (\InvalidArgumentException $e) {
                    throw new ValidationException('Invalid filter operator', [
                        $field => [$e->getMessage()],
                    ]);
                }
                if ($op === FilterOperator::In || $op === FilterOperator::NotIn) {
                    $value = is_array($value) ? $value : array_map('trim', explode(',', (string) $value));
                }

                $filters[] = new Filter($field, $op, $value);
            }
        }

        return $filters;
    }

    /**
     * @return list<Sort>
     */
    private function parseSorts(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $sorts = [];
        foreach (explode(',', (string) $value) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $direction = 'ASC';
            if (str_starts_with($part, '-')) {
                $direction = 'DESC';
                $part = substr($part, 1);
            } else {
                $part = ltrim($part, '+');
            }

            // Nuxt/Strapi-style aliases: field:asc / field:desc
            if (preg_match('/^(.+):(asc|desc)$/i', $part, $matches) === 1) {
                $part = $matches[1];
                $direction = strcasecmp($matches[2], 'desc') === 0 ? 'DESC' : 'ASC';
            }

            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $sorts[] = new Sort($part, $direction);
        }

        return $sorts;
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private function resolveLimit(array $queryParams, int $maxLimit): int
    {
        if (!array_key_exists('limit', $queryParams) || $queryParams['limit'] === '' || $queryParams['limit'] === null) {
            return min(20, max(1, $maxLimit));
        }

        $limit = $this->parseNonNegativeInt($queryParams['limit'], 'limit');
        if ($limit < 1) {
            throw new ValidationException('Invalid limit', ['limit' => ['Limit must be at least 1']]);
        }

        return min($limit, $maxLimit);
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private function resolveOffset(array $queryParams, int $limit, int $maxOffset): int
    {
        $hasOffset = array_key_exists('offset', $queryParams)
            && $queryParams['offset'] !== ''
            && $queryParams['offset'] !== null;
        $hasPage = array_key_exists('page', $queryParams)
            && $queryParams['page'] !== ''
            && $queryParams['page'] !== null;

        if ($hasOffset && $hasPage) {
            throw new ValidationException(
                'Conflicting pagination parameters',
                [
                    'page' => ['Use either page or offset, not both'],
                    'offset' => ['Use either page or offset, not both'],
                ],
            );
        }

        if ($hasOffset) {
            return min($this->parseNonNegativeInt($queryParams['offset'], 'offset'), $maxOffset);
        }

        if (!$hasPage) {
            return 0;
        }

        $page = $this->parseNonNegativeInt($queryParams['page'], 'page');
        if ($page < 1) {
            throw new ValidationException('Invalid page', ['page' => ['Page must be at least 1']]);
        }

        return min(($page - 1) * $limit, $maxOffset);
    }

    private function parseNonNegativeInt(mixed $raw, string $field): int
    {
        if (is_int($raw)) {
            if ($raw < 0) {
                throw new ValidationException('Invalid ' . $field, [$field => ['Must be a non-negative integer']]);
            }

            return $raw;
        }

        if (!is_string($raw) && !is_float($raw)) {
            throw new ValidationException('Invalid ' . $field, [$field => ['Must be a non-negative integer']]);
        }

        $value = trim((string) $raw);
        if ($value === '' || !preg_match('/^\d+$/', $value)) {
            throw new ValidationException('Invalid ' . $field, [$field => ['Must be a non-negative integer']]);
        }

        return (int) $value;
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private function resolveContext(array $queryParams, ?string $contextHeader): string
    {
        $context = $contextHeader !== null && $contextHeader !== ''
            ? $contextHeader
            : (string) ($queryParams['context'] ?? 'web');

        $allowed = array_map('trim', explode(',', (string) $this->modx->getOption('mxheadless.allowed_contexts', null, 'web,mgr')));
        if (!in_array($context, $allowed, true)) {
            throw new ValidationException('Invalid context', ['context' => ['Context not allowed']]);
        }

        return $context;
    }
}
