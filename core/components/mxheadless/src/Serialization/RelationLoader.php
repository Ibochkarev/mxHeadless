<?php

declare(strict_types=1);

namespace MxHeadless\Serialization;

use MODX\Revolution\modX;
use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Definition\RelationDefinition;
use MxHeadless\Query\IncludeTree;
use MxHeadless\Query\ObjectQuery;
use MxHeadless\Query\VisibilityPolicy;
use MxHeadless\Registry\ObjectRegistry;
use xPDOObject;

final class RelationLoader
{
    public function __construct(
        private readonly modX $modx,
        private readonly ObjectRegistry $registry,
        private readonly XpdoObjectSerializer $serializer,
        private readonly VisibilityPolicy $visibility,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function load(
        xPDOObject $object,
        ObjectDefinition $definition,
        IncludeTree $includes,
        SerializeRequest $request,
        ObjectQuery $query,
    ): array {
        if ($includes->isEmpty()) {
            return [];
        }

        $relations = [];
        foreach ($includes->rootRelations() as $relationName) {
            if ($relationName === 'tv') {
                continue;
            }

            $relation = $definition->getRelation($relationName);
            if ($relation === null || !$relation->isReadable()) {
                continue;
            }

            $loaded = $this->loadRelation($object, $definition, $relation, $includes, $request, $query);
            if ($loaded !== null) {
                $relations[$relationName] = $loaded;
            }
        }

        return $relations;
    }

    /**
     * @return mixed
     */
    private function loadRelation(
        xPDOObject $object,
        ObjectDefinition $definition,
        RelationDefinition $relation,
        IncludeTree $includes,
        SerializeRequest $request,
        ObjectQuery $query,
    ): mixed {
        if ($relation->type() === RelationDefinition::TYPE_TO_MANY) {
            return $this->loadToMany($object, $definition, $relation, $request, $query);
        }

        return $this->loadToOne($object, $definition, $relation, $includes, $request, $query);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function loadToOne(
        xPDOObject $object,
        ObjectDefinition $definition,
        RelationDefinition $relation,
        IncludeTree $includes,
        SerializeRequest $request,
        ObjectQuery $query,
    ): ?array {
        $foreignKey = $relation->foreignKey() ?? $relation->name();
        $value = $object->get($foreignKey);
        if ($value === null || $value === '' || $value === 0) {
            return null;
        }

        $targetDefinition = $this->registry->get($relation->targetObject());
        if ($targetDefinition === null) {
            return null;
        }

        $localKey = $relation->localKey() ?? 'id';
        $criteria = $this->visibility->applyCriteria($targetDefinition, $query, [$localKey => $value], $request->request());

        /** @var xPDOObject|null $related */
        $related = $this->modx->getObject($targetDefinition->objectClass(), $criteria);
        if ($related === null) {
            return null;
        }

        $fields = $relation->getFields() !== []
            ? $relation->getFields()
            : $request->authorizer()->resolveReadableFields($request->request(), $targetDefinition);

        $nested = new IncludeTree($includes->nestedPaths($relation->name()));
        $nestedRequest = new SerializeRequest(
            $targetDefinition,
            $fields,
            $request->context(),
            $nested,
            $request->request(),
            $request->authorizer(),
        );

        $data = $this->serializer->serialize($related, $nestedRequest);
        $nestedRelations = $this->load($related, $targetDefinition, $nested, $nestedRequest, $query);
        if ($nestedRelations !== []) {
            $data = array_merge($data, $nestedRelations);
        }

        return $data;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function loadToMany(
        xPDOObject $object,
        ObjectDefinition $definition,
        RelationDefinition $relation,
        SerializeRequest $request,
        ObjectQuery $query,
    ): array {
        $targetDefinition = $this->registry->get($relation->targetObject());
        if ($targetDefinition === null) {
            return [];
        }

        $localKey = $relation->localKey() ?? 'id';
        $foreignKey = $relation->foreignKey() ?? $definition->name() . '_id';
        $parentId = $object->get($localKey);
        if ($parentId === null) {
            return [];
        }

        $criteria = $this->visibility->applyCriteria($targetDefinition, $query, [$foreignKey => $parentId], $request->request());
        /** @var list<xPDOObject> $collection */
        $collection = $this->modx->getCollection($targetDefinition->objectClass(), $criteria);
        $fields = $relation->getFields() !== []
            ? $relation->getFields()
            : $request->authorizer()->resolveReadableFields($request->request(), $targetDefinition);

        $items = [];
        foreach ($collection as $related) {
            $itemRequest = new SerializeRequest(
                $targetDefinition,
                $fields,
                $request->context(),
                new IncludeTree(),
                $request->request(),
                $request->authorizer(),
            );
            $items[] = $this->serializer->serialize($related, $itemRequest);
        }

        return $items;
    }
}
