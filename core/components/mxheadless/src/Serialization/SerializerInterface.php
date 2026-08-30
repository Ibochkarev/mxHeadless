<?php

declare(strict_types=1);

namespace MxHeadless\Serialization;

interface SerializerInterface
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(object $object, SerializeRequest $request): array;
}
