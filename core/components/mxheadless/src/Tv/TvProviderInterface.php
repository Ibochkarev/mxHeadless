<?php

declare(strict_types=1);

namespace MxHeadless\Tv;

use MODX\Revolution\modResource;

interface TvProviderInterface
{
    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getForResource(modResource $resource, array $params = []): array;
}
