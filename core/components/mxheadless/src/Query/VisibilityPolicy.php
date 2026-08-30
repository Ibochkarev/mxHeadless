<?php

declare(strict_types=1);

namespace MxHeadless\Query;

use MxHeadless\Authorization\Authorizer;
use MxHeadless\Definition\ObjectDefinition;
use Psr\Http\Message\ServerRequestInterface;
use xPDOQuery;

final class VisibilityPolicy
{
    public function __construct(
        private readonly Authorizer $authorizer,
    ) {
    }

    /**
     * @param array<string, mixed> $criteria
     * @return array<string, mixed>
     */
    public function applyCriteria(
        ObjectDefinition $definition,
        ObjectQuery $query,
        array $criteria,
        ?ServerRequestInterface $request = null,
        bool $forMutation = false,
        bool $includeDeleted = false,
    ): array {
        if (
            in_array('context_key', $definition->getFields(), true)
            && $query->context() !== ''
            && !$forMutation
        ) {
            $criteria['context_key'] = $query->context();
        }

        if ($query->preview() || $forMutation || $includeDeleted || $query->includeDeleted()) {
            if (
                in_array('deleted', $definition->getFields(), true)
                && !$includeDeleted
                && !$query->includeDeleted()
            ) {
                $criteria['deleted'] = 0;
            }

            return $criteria;
        }

        if (in_array('published', $definition->getFields(), true)) {
            $criteria['published'] = 1;
        }
        if (in_array('deleted', $definition->getFields(), true)) {
            $criteria['deleted'] = 0;
        }

        return $criteria;
    }

    public function applyToQuery(
        ObjectDefinition $definition,
        ObjectQuery $query,
        xPDOQuery $xpdoQuery,
        ?ServerRequestInterface $request = null,
    ): void {
        if (in_array('context_key', $definition->getFields(), true) && $query->context() !== '') {
            $xpdoQuery->where(['context_key' => $query->context()]);
        }

        if ($definition->isContextAccessGated() && $request !== null) {
            $this->applyAccessibleContextKeys($definition, $xpdoQuery, $request);
        }

        if ($query->preview() || $query->includeDeleted()) {
            if (in_array('deleted', $definition->getFields(), true) && !$query->includeDeleted()) {
                $xpdoQuery->where(['deleted' => 0]);
            }

            return;
        }

        if (in_array('published', $definition->getFields(), true)) {
            $xpdoQuery->where(['published' => 1]);
        }
        if (in_array('deleted', $definition->getFields(), true)) {
            $xpdoQuery->where(['deleted' => 0]);
        }
    }

    private function applyAccessibleContextKeys(
        ObjectDefinition $definition,
        xPDOQuery $xpdoQuery,
        ServerRequestInterface $request,
    ): void {
        $keys = $this->authorizer->accessibleContextKeys($request);
        $column = $definition->getPrimaryKey();

        if ($keys === []) {
            $xpdoQuery->where(['1:=' => 0]);

            return;
        }

        $xpdoQuery->where([$column . ':IN' => $keys]);
    }
}
