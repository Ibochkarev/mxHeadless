<?php

declare(strict_types=1);

namespace MxHeadless\Query;

use MODX\Revolution\modX;
use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Exception\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use xPDOQuery;

final class XpdoQueryCompiler
{
    public function __construct(
        private readonly modX $modx,
        private readonly VisibilityPolicy $visibility,
    ) {
    }

    /**
     * @param list<string> $selectFields
     */
    public function compile(
        ObjectDefinition $definition,
        ObjectQuery $query,
        array $selectFields,
        ?ServerRequestInterface $request = null,
    ): xPDOQuery {
        $class = $definition->objectClass();
        if ($class === '') {
            throw new ValidationException('Object class is not configured');
        }

        $xpdoQuery = $this->modx->newQuery($class);
        if (!$xpdoQuery instanceof xPDOQuery) {
            throw new \RuntimeException('Failed to create xPDO query for ' . $class);
        }

        $this->applySelect($xpdoQuery, $definition, $selectFields);
        $this->applyFilters($xpdoQuery, $definition, $query, $request);
        $this->applySearch($xpdoQuery, $definition, $query);
        $this->applySorts($xpdoQuery, $definition, $query);
        $this->applyPagination($xpdoQuery, $query);

        return $xpdoQuery;
    }

    public function compileCount(
        ObjectDefinition $definition,
        ObjectQuery $query,
        ?ServerRequestInterface $request = null,
    ): xPDOQuery {
        $class = $definition->objectClass();
        $xpdoQuery = $this->modx->newQuery($class);
        if (!$xpdoQuery instanceof xPDOQuery) {
            throw new \RuntimeException('Failed to create xPDO count query for ' . $class);
        }

        $this->applyFilters($xpdoQuery, $definition, $query, $request);
        $this->applySearch($xpdoQuery, $definition, $query);

        return $xpdoQuery;
    }

    /**
     * @param list<string> $selectFields
     */
    private function applySelect(xPDOQuery $xpdoQuery, ObjectDefinition $definition, array $selectFields): void
    {
        $allowed = $definition->getFields();
        $alias = $xpdoQuery->getAlias();
        $columns = [];
        foreach ($selectFields as $field) {
            if (!in_array($field, $allowed, true)) {
                throw new ValidationException('Field not allowed', [$field => ['Field is not readable']]);
            }
            $columns[] = $this->qualifiedColumn($alias, $field);
        }

        if ($columns === []) {
            foreach ($allowed as $field) {
                if (!in_array($field, $definition->getHiddenFields(), true)) {
                    $columns[] = $this->qualifiedColumn($alias, $field);
                }
            }
        }

        if ($columns !== []) {
            $xpdoQuery->select($columns);
        }
    }

    private function applyFilters(
        xPDOQuery $xpdoQuery,
        ObjectDefinition $definition,
        ObjectQuery $query,
        ?ServerRequestInterface $request = null,
    ): void {
        $filterable = $definition->getFilterable();

        foreach ($query->filters() as $filter) {
            $field = $filter->field();
            if (!in_array($field, $filterable, true)) {
                throw new ValidationException('Filter not allowed', [$field => ['Field is not filterable']]);
            }

            $safeField = $this->escapeIdentifier($field);
            $criteria = match ($filter->operator()) {
                FilterOperator::Eq => [$safeField => $filter->value()],
                FilterOperator::Neq => [$safeField . ':!=' => $filter->value()],
                FilterOperator::Gt => [$safeField . ':>' => $filter->value()],
                FilterOperator::Gte => [$safeField . ':>=' => $filter->value()],
                FilterOperator::Lt => [$safeField . ':<' => $filter->value()],
                FilterOperator::Lte => [$safeField . ':<=' => $filter->value()],
                FilterOperator::Like => [$safeField . ':LIKE' => $filter->value()],
                FilterOperator::In => [$safeField . ':IN' => array_values((array) $filter->value())],
                FilterOperator::NotIn => [$safeField . ':NOT IN' => array_values((array) $filter->value())],
                FilterOperator::Null => [$safeField . ':IS' => null],
                FilterOperator::NotNull => [$safeField . ':IS NOT' => null],
            };

            if (
                ($filter->operator() === FilterOperator::In || $filter->operator() === FilterOperator::NotIn)
                && $criteria[array_key_first($criteria)] === []
            ) {
                $xpdoQuery->where(['1:=' => 0]);
                continue;
            }

            $xpdoQuery->where($criteria);
        }

        $this->visibility->applyToQuery($definition, $query, $xpdoQuery, $request);
    }

    private function applySearch(xPDOQuery $xpdoQuery, ObjectDefinition $definition, ObjectQuery $query): void
    {
        $term = $query->search();
        if ($term === null) {
            return;
        }

        $searchable = $definition->getSearchable();
        if ($searchable === []) {
            throw new ValidationException('Search not supported for this object');
        }

        $or = [];
        foreach ($searchable as $index => $field) {
            if (!in_array($field, $definition->getFields(), true)) {
                continue;
            }
            $safeField = $this->escapeIdentifier($field);
            $key = $index === 0 ? ($safeField . ':LIKE') : ('OR:' . $safeField . ':LIKE');
            $or[$key] = '%' . $term . '%';
        }

        if ($or !== []) {
            // Nested group: xPDO ORs fields when subsequent keys use the OR: prefix.
            $xpdoQuery->where($or);
        }
    }

    private function applySorts(xPDOQuery $xpdoQuery, ObjectDefinition $definition, ObjectQuery $query): void
    {
        $sortable = $definition->getSortable();
        $sorts = $query->sorts();
        $alias = $xpdoQuery->getAlias();

        if ($sorts === []) {
            $primaryKey = $definition->getPrimaryKey();
            if (in_array($primaryKey, $definition->getFields(), true)) {
                $xpdoQuery->sortby($this->qualifiedColumn($alias, $primaryKey), 'ASC');
            }

            return;
        }

        foreach ($sorts as $sort) {
            $field = $sort->field();
            if (!in_array($field, $sortable, true)) {
                throw new ValidationException('Sort not allowed', [$field => ['Field is not sortable']]);
            }

            $direction = $sort->isAscending() ? 'ASC' : 'DESC';
            $xpdoQuery->sortby($this->qualifiedColumn($alias, $field), $direction);
        }
    }

    private function applyPagination(xPDOQuery $xpdoQuery, ObjectQuery $query): void
    {
        $xpdoQuery->limit($query->pagination()->limit(), $query->pagination()->offset());
    }

    private function qualifiedColumn(string $alias, string $field): string
    {
        $column = $this->escapeIdentifier($field);

        return $alias !== '' ? $alias . '.' . $column : $column;
    }

    private function escapeIdentifier(string $field): string
    {
        return str_replace(['`', '.', ' '], '', $field);
    }
}
