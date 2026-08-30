<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MxHeadless\Cache\ModxCacheAdapter;
use MxHeadless\Webhook\WebhookEventFactory;
use MxHeadless\Webhook\WebhookOutbox;
use xPDOObject;

final class MutationHooks
{
    public function __construct(
        private readonly ModxCacheAdapter $cache,
        private readonly WebhookOutbox $webhooks,
    ) {
    }

    public function afterMutation(string $name, string $action, xPDOObject $object, ?string $id = null): void
    {
        $this->cache->invalidateTag('object:' . $name);

        $context = (string) ($object->get('context_key') ?? 'web');
        $this->cache->invalidateTag('context:' . $context);

        $event = $name . '.' . $action;
        $this->webhooks->enqueue($event, WebhookEventFactory::build($name, $action, $object, $id));
    }
}
