<?php

declare(strict_types=1);

namespace MxHeadless\Services;

use MODX\Revolution\modX;
use MxHeadless\Authentication\OAuthClientRepository;
use MxHeadless\Authentication\OAuthTokenRepository;
use MxHeadless\Exception\InvalidGrantException;
use MxHeadless\Exception\ValidationException;
use Psr\Http\Message\ServerRequestInterface;

final class TokenService
{
    public function __construct(
        private readonly modX $modx,
        private readonly OAuthClientRepository $clients,
        private readonly OAuthTokenRepository $tokens,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function handle(ServerRequestInterface $request): array
    {
        if (!(bool) $this->modx->getOption('mxheadless_oauth_enabled', null, false)) {
            throw new InvalidGrantException('OAuth token endpoint is disabled');
        }

        $payload = $this->parsePayload($request);
        $grantType = (string) ($payload['grant_type'] ?? '');
        if ($grantType === '') {
            throw new ValidationException('grant_type is required', [
                'grant_type' => ['This field is required.'],
            ]);
        }

        return match ($grantType) {
            'client_credentials' => $this->issueGrant($payload, 'client_credentials'),
            'password' => $this->issueGrant($payload, 'password'),
            default => throw new InvalidGrantException('Unsupported grant_type'),
        };
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function issueGrant(array $payload, string $grantType): array
    {
        if ($grantType === 'password' && !(bool) $this->modx->getOption('mxheadless_oauth_password_grant_enabled', null, false)) {
            throw new InvalidGrantException('Password grant is disabled');
        }

        $client = $this->authenticateClient($payload);
        if (!$this->clients->allowsGrantType($client, $grantType)) {
            throw new InvalidGrantException('Client is not allowed to use ' . $grantType);
        }

        $userId = null;
        if ($grantType === 'password') {
            $this->requireFields($payload, ['username', 'password']);
            $userId = $this->verifyUserCredentials(
                trim((string) $payload['username']),
                (string) $payload['password'],
            );
            if ($userId === null) {
                throw new InvalidGrantException('Invalid username or password');
            }
        }

        return $this->tokenResponse(
            (int) $client['id'],
            $this->resolveScopes($client, $payload),
            $userId,
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function authenticateClient(array $payload): array
    {
        $this->requireFields($payload, ['client_id', 'client_secret']);

        $client = $this->clients->verify(
            trim((string) $payload['client_id']),
            (string) $payload['client_secret'],
        );
        if ($client === null) {
            throw new InvalidGrantException('Invalid client credentials');
        }

        return $client;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<string> $fields
     */
    private function requireFields(array $payload, array $fields): void
    {
        $errors = [];
        foreach ($fields as $field) {
            $value = $payload[$field] ?? null;
            if (!is_string($value) || trim($value) === '') {
                $errors[$field] = ['This field is required.'];
            }
        }

        if ($errors !== []) {
            throw new ValidationException('Missing required fields', $errors);
        }
    }

    /**
     * @param array<string, mixed> $client
     * @param array<string, mixed> $payload
     * @return list<string>
     */
    private function resolveScopes(array $client, array $payload): array
    {
        $allowed = $this->clients->scopesFor($client);
        if ($allowed === []) {
            return ['*'];
        }

        $requested = trim((string) ($payload['scope'] ?? ''));
        if ($requested === '') {
            return $allowed;
        }

        $requestedScopes = array_values(array_filter(array_map('trim', explode(' ', $requested))));
        foreach ($requestedScopes as $scope) {
            if (!in_array($scope, $allowed, true) && !in_array('*', $allowed, true)) {
                throw new InvalidGrantException('Requested scope is not allowed for this client');
            }
        }

        return $requestedScopes;
    }

    /**
     * @param list<string> $scopes
     * @return array<string, mixed>
     */
    private function tokenResponse(int $clientId, array $scopes, ?int $userId = null): array
    {
        $issued = $this->tokens->issue($clientId, $scopes, $userId);

        return [
            'data' => [
                'access_token' => $issued['token'],
                'token_type' => 'Bearer',
                'expires_in' => $issued['expires_in'],
                'scope' => implode(' ', $scopes),
            ],
            'meta' => [],
        ];
    }

    private function verifyUserCredentials(string $username, string $password): ?int
    {
        /** @var object|null $user */
        $user = $this->modx->getObject('modUser', ['username' => $username]);
        if ($user === null || !method_exists($user, 'get') || !method_exists($user, 'passwordMatches')) {
            return null;
        }

        if (!$user->passwordMatches($password)) {
            return null;
        }

        $userId = (int) $user->get('id');

        return $userId > 0 ? $userId : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function parsePayload(ServerRequestInterface $request): array
    {
        $parsed = $request->getParsedBody();
        $payload = is_array($parsed) && $parsed !== [] ? $parsed : [];

        if ($payload === []) {
            $raw = trim((string) $request->getBody());
            if ($raw !== '') {
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $payload = $json;
                } else {
                    parse_str($raw, $form);
                    if (is_array($form) && $form !== []) {
                        $payload = $form;
                    } else {
                        throw new ValidationException('Request body must be JSON or form-urlencoded');
                    }
                }
            }
        }

        return $this->mergeBasicAuthCredentials($request, $payload);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function mergeBasicAuthCredentials(ServerRequestInterface $request, array $payload): array
    {
        $authorization = trim($request->getHeaderLine('Authorization'));
        if ($authorization === '' || preg_match('/^Basic\s+(\S+)$/i', $authorization, $matches) !== 1) {
            return $payload;
        }

        $decoded = base64_decode($matches[1], true);
        if ($decoded === false || !str_contains($decoded, ':')) {
            return $payload;
        }

        [$clientId, $clientSecret] = explode(':', $decoded, 2);
        if (!isset($payload['client_id']) || !is_string($payload['client_id']) || trim($payload['client_id']) === '') {
            $payload['client_id'] = $clientId;
        }
        if (!isset($payload['client_secret']) || !is_string($payload['client_secret']) || trim($payload['client_secret']) === '') {
            $payload['client_secret'] = $clientSecret;
        }

        return $payload;
    }
}
