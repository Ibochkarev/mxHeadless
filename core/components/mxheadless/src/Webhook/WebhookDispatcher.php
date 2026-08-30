<?php

declare(strict_types=1);

namespace MxHeadless\Webhook;

use MODX\Revolution\modX;
use MxHeadless\Exception\HttpException;
use MxHeadless\Http\Psr7ServiceResolver;
use MxHeadless\Model\modMxHeadlessWebhookDelivery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class WebhookDispatcher
{
    public function __construct(
        private readonly modX $modx,
        private readonly WebhookOutbox $outbox,
        private readonly WebhookSigner $signer,
    ) {
    }

    public function processPending(int $limit = 50): int
    {
        $processed = 0;

        foreach ($this->outbox->pending($limit) as $delivery) {
            $url = (string) $delivery->get('url');
            if ($url === '' || !$this->isSafeUrl($url)) {
                $this->outbox->markFailed($delivery, 'Unsafe or missing webhook URL');
                continue;
            }

            $payload = (string) $delivery->get('payload');
            $secret = (string) $delivery->get('secret');
            $event = (string) $delivery->get('event');
            $headers = [
                'Content-Type' => 'application/json',
                'User-Agent' => 'MxHeadless-Webhook/1.0',
                'X-MxHeadless-Event' => $event,
                'X-MxHeadless-Delivery-Id' => (string) $delivery->get('id'),
            ];
            if ($secret !== '') {
                $headers['X-MxHeadless-Signature'] = $this->signer->sign($secret, $payload);
            }

            try {
                $response = $this->send($url, $payload, $headers);
                if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                    $this->outbox->markDelivered($delivery);
                    ++$processed;
                } else {
                    $this->outbox->markFailed($delivery, 'HTTP ' . $response->getStatusCode());
                }
            } catch (\Throwable $e) {
                $this->outbox->markFailed($delivery, $e->getMessage());
            }
        }

        return $processed;
    }

    /**
     * @param array<string, string> $headers
     */
    private function send(string $url, string $payload, array $headers): ResponseInterface
    {
        if (!$this->modx->services->has(ClientInterface::class)) {
            throw new HttpException('HTTP client is not configured for webhook delivery', 500);
        }

        $client = $this->modx->services->get(ClientInterface::class);
        $requestFactory = Psr7ServiceResolver::resolve($this->modx, RequestFactoryInterface::class);
        $streamFactory = Psr7ServiceResolver::resolve($this->modx, StreamFactoryInterface::class);

        $request = $requestFactory->createRequest('POST', $url)
            ->withBody($streamFactory->createStream($payload));
        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        return $client->sendRequest($request);
    }

    private function isSafeUrl(string $url): bool
    {
        $parts = parse_url($url);
        if ($parts === false) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));
        if ($host === '') {
            return false;
        }

        $allowPrivate = (bool) $this->modx->getOption('mxheadless_webhook_allow_private_urls', null, false);
        if (!$allowPrivate && ($host === 'localhost' || str_ends_with($host, '.local') || str_ends_with($host, '.test'))) {
            return false;
        }

        $ip = gethostbyname($host);
        if ($ip !== $host && filter_var($ip, FILTER_VALIDATE_IP)) {
            $isPrivate = !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
            if ($isPrivate && !$allowPrivate) {
                return false;
            }
        }

        return true;
    }
}
