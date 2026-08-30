<?php

declare(strict_types=1);

namespace MxHeadless\Tests\Unit\Webhook;

use MxHeadless\Webhook\WebhookEventFactory;
use PHPUnit\Framework\TestCase;
use xPDOObject;

final class WebhookEventFactoryTest extends TestCase
{
    public function testBuildsIsrPayloadWithRevalidationTags(): void
    {
        $object = $this->resourceObject();

        $event = WebhookEventFactory::build('resources', 'updated', $object);

        self::assertSame('resources.updated', $event['type']);
        self::assertSame('resources', $event['data']['object']);
        self::assertSame('updated', $event['data']['action']);
        self::assertSame('42', $event['data']['id']);
        self::assertContains('mxheadless:resources:42', $event['meta']['revalidate']);
        self::assertContains('mxheadless:uri:about.html', $event['meta']['revalidate']);
        self::assertContains('mxheadless:context:web', $event['meta']['revalidate']);
        self::assertContains('mxheadless:resources:1', $event['meta']['revalidate']);
    }

    public function testParentTagUsesObjectName(): void
    {
        $object = $this->resourceObject();
        $event = WebhookEventFactory::build('chunks', 'updated', $object);

        self::assertContains('mxheadless:chunks:1', $event['meta']['revalidate']);
        self::assertNotContains('mxheadless:resources:1', $event['meta']['revalidate']);
    }

    public function testDeletedResourceAddsListTag(): void
    {
        $event = WebhookEventFactory::build('resources', 'deleted', $this->resourceObject());

        self::assertContains('mxheadless:resources:list', $event['meta']['revalidate']);
    }

    private function resourceObject(): xPDOObject
    {
        return new class extends xPDOObject {
            /** @var array<string, mixed> */
            private array $data = [
                'id' => 42,
                'context_key' => 'web',
                'uri' => 'about.html',
                'parent' => 1,
            ];

            public function get($key): mixed
            {
                return $this->data[$key] ?? null;
            }
        };
    }
}
