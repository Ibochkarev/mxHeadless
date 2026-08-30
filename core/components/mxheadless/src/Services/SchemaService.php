<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MxHeadless\Registry\ObjectRegistry;

final class SchemaService
{
    public function __construct(
        private readonly ObjectRegistry $registry,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(): array
    {
        $objects = [];
        foreach ($this->registry->all() as $name => $definition) {
            $objects[$name] = [
                'class' => $definition->objectClass(),
                'fields' => $definition->getFields(),
                'filterable' => $definition->getFilterable(),
                'sortable' => $definition->getSortable(),
                'searchable' => $definition->getSearchable(),
                'required' => $definition->getRequiredFields(),
                'protected' => $definition->getProtectedFields(),
                'immutable' => $definition->getImmutableFields(),
                'readable' => $definition->isReadable(),
                'creatable' => $definition->isCreatable(),
                'updatable' => $definition->isUpdatable(),
                'deletable' => $definition->isDeletable(),
                'relations' => array_map(
                    static fn ($relation): array => [
                        'name' => $relation->name(),
                        'target' => $relation->targetObject(),
                        'type' => $relation->type(),
                    ],
                    $definition->relations(),
                ),
            ];
        }

        return [
            'data' => ['objects' => $objects],
            'meta' => ['count' => count($objects)],
        ];
    }
}
