<?php

declare(strict_types=1);

namespace MxHeadless\Webhook;

use MODX\Revolution\modX;
use MxHeadless\Model\modMxHeadlessWebhookDelivery;
use MxHeadless\Model\modMxHeadlessWebhookSubscription;

final class WebhookOutbox
{
    public function __construct(
        private readonly modX $modx,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function enqueue(string $event, array $payload): int
    {
        $enqueued = 0;
        foreach ($this->matchingSubscriptions($event) as $subscription) {
            if ($this->createDelivery($subscription, $event, $payload)) {
                ++$enqueued;
            }
        }

        return $enqueued;
    }

    /**
     * @return list<modMxHeadlessWebhookDelivery>
     */
    public function pending(int $limit = 50): array
    {
        $now = time();
        $query = $this->modx->newQuery(modMxHeadlessWebhookDelivery::class);
        $query->where(['status' => 'pending']);
        $query->where([
            'next_attempt_on:IS' => null,
            'OR:next_attempt_on:<=' => $now,
        ]);
        $query->sortby('created_on', 'ASC');
        $query->limit(max(1, $limit));

        /** @var list<modMxHeadlessWebhookDelivery> $collection */
        $collection = $this->modx->getCollection(modMxHeadlessWebhookDelivery::class, $query);

        return array_values($collection);
    }

    public function markDelivered(modMxHeadlessWebhookDelivery $delivery): void
    {
        $delivery->set('status', 'delivered');
        $delivery->set('attempts', (int) $delivery->get('attempts') + 1);
        $delivery->set('delivered_on', time());
        $delivery->set('next_attempt_on', null);
        $delivery->set('last_error', null);
        $delivery->save();
    }

    public function markFailed(modMxHeadlessWebhookDelivery $delivery, string $error): void
    {
        $maxAttempts = max(1, (int) $this->modx->getOption('mxheadless.webhook.max_attempts', null, 5));
        $attempts = (int) $delivery->get('attempts') + 1;
        $delivery->set('attempts', $attempts);
        $delivery->set('last_error', $error);

        if ($attempts >= $maxAttempts) {
            $delivery->set('status', 'failed');
            $delivery->set('next_attempt_on', null);
        } else {
            $delivery->set('status', 'pending');
            $delivery->set('next_attempt_on', time() + $this->backoffSeconds($attempts));
        }

        $delivery->save();
    }

    private function backoffSeconds(int $attempts): int
    {
        return min(3600, 30 * (2 ** max(0, $attempts - 1)));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function matchingSubscriptions(string $event): array
    {
        $matches = [];
        /** @var list<modMxHeadlessWebhookSubscription> $collection */
        $collection = $this->modx->getCollection(modMxHeadlessWebhookSubscription::class, ['active' => 1]);
        foreach ($collection as $subscription) {
            $events = $this->decodeEvents($subscription->get('events'));
            if ($events === [] || in_array('*', $events, true) || in_array($event, $events, true)) {
                $matches[] = $subscription->toArray();
            }
        }

        return $matches;
    }

    /**
     * @param array<string, mixed> $subscription
     * @param array<string, mixed> $payload
     */
    private function createDelivery(array $subscription, string $event, array $payload): bool
    {
        $url = (string) ($subscription['url'] ?? '');
        if ($url === '') {
            return false;
        }

        /** @var modMxHeadlessWebhookDelivery|null $object */
        $object = $this->modx->newObject(modMxHeadlessWebhookDelivery::class, [
            'subscription_id' => (int) ($subscription['id'] ?? 0),
            'event' => $event,
            'payload' => json_encode($payload, JSON_THROW_ON_ERROR),
            'url' => $url,
            'secret' => (string) ($subscription['secret'] ?? ''),
            'status' => 'pending',
            'attempts' => 0,
            'created_on' => time(),
        ]);

        return $object instanceof modMxHeadlessWebhookDelivery && $object->save();
    }

    /**
     * @return list<string>
     */
    private function decodeEvents(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_filter(array_map('strval', $raw)));
        }

        if (!is_string($raw) || $raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter(array_map('strval', $decoded))) : [];
    }
}
