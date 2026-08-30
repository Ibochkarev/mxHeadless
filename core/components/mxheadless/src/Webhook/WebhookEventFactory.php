<?php

declare(strict_types=1);

namespace MxHeadless\Webhook;

use xPDOObject;

final class WebhookEventFactory
{
    /**
     * @return array{
     *     id: string,
     *     type: string,
     *     created_at: string,
     *     data: array<string, mixed>,
     *     meta: array{revalidate: list<string>}
     * }
     */
    public static function build(string $objectName, string $action, xPDOObject $object, ?string $id = null): array
    {
        $data = self::objectData($objectName, $action, $object, $id);
        $event = $objectName . '.' . $action;

        return [
            'id' => bin2hex(random_bytes(16)),
            'type' => $event,
            'created_at' => gmdate('c'),
            'data' => $data,
            'meta' => [
                'revalidate' => self::revalidationTags($objectName, $action, $data),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function objectData(string $objectName, string $action, xPDOObject $object, ?string $id): array
    {
        $data = [
            'object' => $objectName,
            'action' => $action,
            'id' => $id ?? self::resolvePrimaryId($object),
        ];

        $context = $object->get('context_key');
        if ($context !== null && $context !== '') {
            $data['context'] = (string) $context;
        }

        $uri = $object->get('uri');
        if ($uri !== null && $uri !== '') {
            $data['uri'] = (string) $uri;
        }

        $parent = $object->get('parent');
        if ($action !== 'created' && $parent !== null && $parent !== '' && (int) $parent > 0) {
            $data['parent'] = (int) $parent;
        }

        return $data;
    }

    /**
     * @param array<string, mixed> $data
     * @return list<string>
     */
    private static function revalidationTags(string $objectName, string $action, array $data): array
    {
        $tags = ['mxheadless:' . $objectName];

        if (isset($data['context']) && is_string($data['context']) && $data['context'] !== '') {
            $tags[] = 'mxheadless:context:' . $data['context'];
        }

        if (isset($data['id']) && (string) $data['id'] !== '') {
            $tags[] = 'mxheadless:' . $objectName . ':' . $data['id'];
        }

        if (isset($data['uri']) && is_string($data['uri']) && $data['uri'] !== '') {
            $tags[] = 'mxheadless:uri:' . trim($data['uri'], '/');
        }

        if (isset($data['parent']) && (int) $data['parent'] > 0) {
            $tags[] = 'mxheadless:' . $objectName . ':' . (int) $data['parent'];
        }

        if ($action === 'deleted' && $objectName === 'resources') {
            $tags[] = 'mxheadless:resources:list';
        }

        return array_values(array_unique($tags));
    }

    private static function resolvePrimaryId(xPDOObject $object): string
    {
        $id = $object->get('id');
        if ($id !== null && $id !== '') {
            return (string) $id;
        }

        $key = $object->get('key');
        if ($key !== null && $key !== '') {
            return (string) $key;
        }

        return '';
    }
}
