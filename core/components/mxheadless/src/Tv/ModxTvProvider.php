<?php

declare(strict_types=1);

namespace MxHeadless\Tv;

use MODX\Revolution\modResource;
use MODX\Revolution\modTemplateVar;

final class ModxTvProvider implements TvProviderInterface
{
    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function getForResource(modResource $resource, array $params = []): array
    {
        $requested = $this->parseRequestedFields($params);
        $values = $resource->getMany('TemplateVars');
        if (!is_array($values)) {
            return [];
        }

        $result = [];
        foreach ($values as $tv) {
            if (!$tv instanceof modTemplateVar) {
                continue;
            }

            $name = (string) $tv->get('name');
            if ($requested !== [] && !in_array($name, $requested, true)) {
                continue;
            }

            $result[$name] = $tv->getValue($resource->get('id'));
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<string>
     */
    private function parseRequestedFields(array $params): array
    {
        $raw = $params['tv_fields'] ?? null;
        if ($raw === null || $raw === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $raw))));
    }
}
