<?php

declare(strict_types=1);

namespace MxHeadless\Serialization;

use MxHeadless\Media\MediaUrlResolver;
use xPDOObject;

final class XpdoObjectSerializer implements SerializerInterface
{
    public function __construct(
        private readonly ?MediaUrlResolver $mediaUrlResolver = null,
    ) {
    }

    public function serialize(object $object, SerializeRequest $request): array
    {
        if (!$object instanceof xPDOObject) {
            throw new \InvalidArgumentException('Expected xPDOObject instance');
        }

        $definition = $request->definition();
        $fields = $request->fields();
        $allowed = $definition->getFields();
        $hidden = $definition->getHiddenFields();

        if ($fields === []) {
            $fields = array_values(array_filter($allowed, static fn (string $field): bool => !in_array($field, $hidden, true)));
        }

        $data = [];
        foreach ($fields as $field) {
            if (!in_array($field, $allowed, true) || in_array($field, $hidden, true)) {
                continue;
            }

            if (!$request->canReadField($field)) {
                continue;
            }

            if (in_array($field, $definition->getProtectedFields(), true) && !$request->canReadField($field, true)) {
                continue;
            }

            $value = $object->get($field);
            if ($this->mediaUrlResolver !== null && $this->isMediaField($field) && is_string($value) && $value !== '') {
                $value = $this->mediaUrlResolver->resolve($value, $request->context());
            }

            $data[$field] = $value;
        }

        return $data;
    }

    private function isMediaField(string $field): bool
    {
        return in_array($field, ['image', 'thumbnail', 'photo', 'avatar', 'file', 'media'], true);
    }
}
