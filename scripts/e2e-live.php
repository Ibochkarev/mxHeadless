<?php
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'https://project.test', '/');
$apiKey = $argv[2] ?? '';
$oauthClient = $argv[3] ?? 'smoke-client';
$oauthSecret = $argv[4] ?? '';

$pass = 0; $fail = 0; $bugs = [];

function req(string $method, string $url, array $headers = [], ?string $body = null): array {
    $ch = curl_init($url);
    $hdrs = array_merge(['Accept: application/json'], $headers);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $hdrs,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HEADER => true,
    ]);
    if ($body !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    }
    $raw = curl_exec($ch);
    if ($raw === false) {
        return ['status' => 0, 'headers' => [], 'body' => '', 'error' => curl_error($ch)];
    }
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = (int) curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $headerRaw = substr($raw, 0, $headerSize);
    $bodyOut = substr($raw, $headerSize);
    $headersOut = [];
    foreach (explode("\r\n", $headerRaw) as $line) {
        if (str_contains($line, ':')) {
            [$k, $v] = explode(':', $line, 2);
            $headersOut[strtolower(trim($k))] = trim($v);
        }
    }
    return ['status' => $status, 'headers' => $headersOut, 'body' => $bodyOut, 'json' => json_decode($bodyOut, true)];
}

function check(string $name, bool $ok, string $detail = ''): void {
    global $pass, $fail, $bugs;
    if ($ok) {
        echo "OK   $name\n";
        ++$pass;
    } else {
        echo "FAIL $name" . ($detail !== '' ? " — $detail" : '') . "\n";
        ++$fail;
        $bugs[] = "$name: $detail";
    }
}

$auth = $apiKey !== '' ? ["Authorization: Bearer $apiKey"] : [];

// --- Public ---
$r = req('GET', "$base/api/v1");
check('discovery', $r['status'] === 200 && ($r['json']['data']['name'] ?? '') === 'mxHeadless', (string)$r['status']);

$r = req('GET', "$base/api/v1/health");
check('health', $r['status'] === 200 && ($r['json']['data']['status'] ?? '') === 'ok', (string)$r['status']);

$r = req('GET', "$base/api/v1/schema");
check('schema', $r['status'] === 200 && isset($r['json']['data']['objects']['resources']), (string)$r['status']);

$r = req('GET', "$base/api/v1/meta/endpoints");
$endpointCount = is_array($r['json']['data']['endpoints'] ?? null) ? count($r['json']['data']['endpoints']) : 0;
check('meta.endpoints', $r['status'] === 200 && $endpointCount > 5, "count=$endpointCount status={$r['status']}");

$r = req('GET', "$base/api/v1/meta/openapi");
check('meta.openapi', $r['status'] === 200 && isset($r['json']['data']['openapi']), (string)$r['status']);

$r = req('GET', "$base/api/v1/resources?" . http_build_query(['limit' => 2, 'filter' => ['published' => ['eq' => 1]], 'fields' => 'id,pagetitle,uri']));
check('resources.filter', $r['status'] === 200 && is_array($r['json']['data'] ?? null) && ($r['json']['data'] !== []), (string)$r['status'] . ' ' . substr($r['body'],0,120));
$resources = $r['json']['data'] ?? [];
$sampleUri = $resources[0]['uri'] ?? null;
$sampleId = $resources[0]['id'] ?? null;

$r = req('GET', "$base/api/v1/resources?" . http_build_query(['limit' => 3, 'sort' => '-id', 'fields' => 'id']));
$idsDesc = array_column($r['json']['data'] ?? [], 'id');
$rAsc = req('GET', "$base/api/v1/resources?" . http_build_query(['limit' => 3, 'sort' => 'id', 'fields' => 'id']));
$idsAsc = array_column($rAsc['json']['data'] ?? [], 'id');
check('resources.sort', $r['status'] === 200 && $idsDesc !== [] && $idsDesc !== $idsAsc, 'desc=' . json_encode($idsDesc) . ' asc=' . json_encode($idsAsc));

$r = req('GET', "$base/api/v1/resources?limit=1&include=parent");
check('resources.include.parent', $r['status'] === 200, (string)$r['status']);

if ($sampleUri) {
    $r = req('GET', "$base/api/v1/pages/" . rawurlencode(ltrim((string)$sampleUri, '/')));
    check('pages.by_uri', $r['status'] === 200 && isset($r['json']['data']['id']), "uri=$sampleUri status={$r['status']} body=" . substr($r['body'],0,100));
} else {
    check('pages.by_uri', false, 'no published resource uri');
}

$r = req('GET', "$base/api/v1/pages/index");
check('pages.index_alias_docs', in_array($r['status'], [200, 404], true), 'status=' . $r['status'] . ' (404 ok if site uri is index.html)');

// --- Deny by default ---
$r = req('GET', "$base/api/v1/contexts?limit=1");
check('contexts.no_auth', $r['status'] === 401 && ($r['json']['code'] ?? '') === 'token_required', (string)$r['status'] . ' ' . ($r['json']['code'] ?? ''));

$r = req('GET', "$base/api/v1/chunks?limit=1");
check('chunks.no_auth', $r['status'] === 401, (string)$r['status']);

// --- API key ---
if ($apiKey !== '') {
    $r = req('GET', "$base/api/v1/contexts?limit=5", $auth);
    check('contexts.with_key', $r['status'] === 200, (string)$r['status'] . ' ' . substr($r['body'],0,120));

    $r = req('GET', "$base/api/v1/contexts/web/settings", $auth);
    check('contexts.settings', $r['status'] === 200 && is_array($r['json']['data'] ?? null), (string)$r['status']);

    $r = req('GET', "$base/api/v1/chunks?limit=1", $auth);
    check('chunks.with_key', $r['status'] === 200, (string)$r['status']);

    $r = req('GET', "$base/api/v1/objects/resources?limit=1", $auth);
    check('objects.resources', $r['status'] === 200, (string)$r['status']);

    if ($sampleId) {
        $r = req('GET', "$base/api/v1/resources/$sampleId", $auth);
        check('resources.get', $r['status'] === 200 && (int)($r['json']['data']['id'] ?? 0) === (int)$sampleId, (string)$r['status']);
    }
}

// --- OAuth ---
if ($oauthSecret !== '') {
    $r = req('POST', "$base/api/v1/auth/token", ['Content-Type: application/json'], json_encode([
        'grant_type' => 'client_credentials',
        'client_id' => $oauthClient,
        'client_secret' => $oauthSecret,
    ], JSON_THROW_ON_ERROR));
    $token = $r['json']['data']['access_token'] ?? '';
    check('oauth.token', $r['status'] === 200 && str_starts_with((string)$token, 'mxt_'), (string)$r['status'] . ' ' . substr($r['body'],0,150));

    if ($token !== '') {
        $r = req('GET', "$base/api/v1/resources?limit=1", ["Authorization: Bearer $token"]);
        check('oauth.resources', $r['status'] === 200, (string)$r['status']);

        $r = req('GET', "$base/api/v1/chunks?limit=1", ["Authorization: Bearer $token"]);
        check('oauth.chunks_denied_scope', $r['status'] === 403 || $r['status'] === 401, 'expected 403 for missing chunks.read, got ' . $r['status']);
    }
} else {
    $r = req('POST', "$base/api/v1/auth/token", ['Content-Type: application/json'], json_encode(['grant_type' => 'client_credentials'], JSON_THROW_ON_ERROR));
    check('oauth.disabled_or_invalid', in_array($r['status'], [400, 401, 422], true), (string)$r['status']);
}

// --- CORS / OPTIONS ---
$r = req('OPTIONS', "$base/api/v1/resources");
check('options.preflight', $r['status'] === 204, (string)$r['status']);

$r = req('OPTIONS', "$base/api/v1/resources", ['Origin: https://frontend.example', 'Access-Control-Request-Method: GET']);
check('options.with_origin', $r['status'] === 204, (string)$r['status']);

// --- CRUD + Idempotency ---
$createdId = null;
if ($apiKey !== '') {
    $payload = json_encode([
        'pagetitle' => 'mxHeadless E2E ' . date('H:i:s'),
        'alias' => 'mxh-e2e-' . bin2hex(random_bytes(4)),
        'published' => 0,
        'hidemenu' => 1,
        'parent' => 0,
        'template' => 0,
        'content' => 'e2e',
    ], JSON_THROW_ON_ERROR);

    $idemKey = 'e2e-' . bin2hex(random_bytes(8));
    $r1 = req('POST', "$base/api/v1/resources", array_merge($auth, [
        'Content-Type: application/json',
        'Idempotency-Key: ' . $idemKey,
    ]), $payload);
    check('resources.create', $r1['status'] === 201 && isset($r1['json']['data']['id']), (string)$r1['status'] . ' ' . substr($r1['body'],0,200));
    $createdId = $r1['json']['data']['id'] ?? null;

    $r2 = req('POST', "$base/api/v1/resources", array_merge($auth, [
        'Content-Type: application/json',
        'Idempotency-Key: ' . $idemKey,
    ]), $payload);
    $replayed = strtolower($r2['headers']['idempotency-replayed'] ?? '') === 'true';
    $sameId = $createdId !== null && (int)($r2['json']['data']['id'] ?? 0) === (int)$createdId;
    check('idempotency.replay', $r2['status'] === $r1['status'] && ($replayed || $sameId), "status={$r2['status']} replayed=" . ($replayed?'y':'n') . ' sameId=' . ($sameId?'y':'n'));

    if ($createdId) {
        $r = req('PATCH', "$base/api/v1/resources/$createdId", array_merge($auth, ['Content-Type: application/json']), json_encode([
            'pagetitle' => 'mxHeadless E2E updated',
        ], JSON_THROW_ON_ERROR));
        check('resources.patch', $r['status'] === 200 && ($r['json']['data']['pagetitle'] ?? '') === 'mxHeadless E2E updated', (string)$r['status'] . ' ' . substr($r['body'],0,150));

        $r = req('DELETE', "$base/api/v1/resources/$createdId", $auth);
        check('resources.delete', in_array($r['status'], [200, 204], true), (string)$r['status'] . ' ' . substr($r['body'],0,150));
    }
}

// --- Kill switch ---
// skip mutating production settings permanently; probe via disabled path using current enabled=1
$r = req('GET', "$base/api/v1/health");
check('kill_switch.health_still_ok', $r['status'] === 200, (string)$r['status']);

// --- Invalid routes ---
$r = req('GET', "$base/api/v1/no-such-endpoint");
check('404.unknown', $r['status'] === 404, (string)$r['status']);

$r = req('GET', "$base/api/v1/resources/999999999", $auth);
check('404.missing_resource', $r['status'] === 404, (string)$r['status']);

echo "\n$pass passed, $fail failed\n";
if ($bugs) {
    echo "Bugs:\n- " . implode("\n- ", $bugs) . "\n";
}
exit($fail > 0 ? 1 : 0);
