<?php
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'https://project.test', '/');
$apiKey = $argv[2] ?? '';
$oauthClient = $argv[3] ?? 'smoke-client';
$oauthSecret = $argv[4] ?? '';

$pass = 0; $fail = 0; $bugs = [];

function req(string $method, string $url, array $headers = [], ?string $body = null): array {
    $ch = curl_init($url);
    $hasAccept = false;
    foreach ($headers as $header) {
        if (stripos($header, 'Accept:') === 0) {
            $hasAccept = true;
            break;
        }
    }
    $hdrs = $hasAccept ? $headers : array_merge(['Accept: application/json'], $headers);
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

$r = req('GET', "$base/api/v1/meta/openapi.json");
check(
    'meta.openapi.json',
    $r['status'] === 200
        && ($r['json']['openapi'] ?? null) === '3.0.3'
        && !isset($r['json']['data']['openapi']),
    (string)$r['status'] . ' ' . substr($r['body'], 0, 80),
);

$r = req('GET', "$base/api/v1/docs", ['Accept: text/html']);
check(
    'docs.swagger',
    $r['status'] === 200
        && str_contains($r['headers']['content-type'] ?? '', 'text/html')
        && str_contains($r['body'], 'SwaggerUIBundle')
        && str_contains($r['body'], '/meta/openapi.json'),
    (string)$r['status'] . ' ct=' . ($r['headers']['content-type'] ?? ''),
);

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

$r = req('GET', "$base/api/v1/templates?limit=1");
check('templates.no_auth', $r['status'] === 401, (string)$r['status']);

$r = req('GET', "$base/api/v1/snippets?limit=1");
check('snippets.no_auth', $r['status'] === 401 && ($r['json']['code'] ?? '') === 'token_required', (string)$r['status'] . ' ' . ($r['json']['code'] ?? ''));

$r = req('GET', "$base/api/v1/categories?limit=1");
check('categories.no_auth', $r['status'] === 401, (string)$r['status']);

$r = req('GET', "$base/api/v1/content_types?limit=1");
check('content_types.no_auth', $r['status'] === 401, (string)$r['status']);

$r = req('GET', "$base/api/v1/tvs?limit=1");
check('tvs.no_auth', $r['status'] === 401, (string)$r['status']);

$r = req('GET', "$base/api/v1/resources?include_deleted=1&limit=1");
check('resources.include_deleted_anon', $r['status'] === 403, (string)$r['status'] . ' ' . ($r['json']['code'] ?? ''));

$r = req('GET', "$base/api/v1/resources?" . http_build_query(['limit' => 2, 'sort' => 'id:asc', 'fields' => 'id']));
check('resources.sort_asc_alias', $r['status'] === 200 && is_array($r['json']['data'] ?? null), (string)$r['status']);

// URI over limit → 414
$longUri = str_repeat('a', 3000);
$r = req('GET', "$base/api/v1/pages/" . $longUri);
check('limits.uri_too_long', $r['status'] === 414, (string)$r['status']);

if ($apiKey !== '') {
    $bigPath = sys_get_temp_dir() . '/mxh-e2e-body.json';
    file_put_contents($bigPath, json_encode(['pagetitle' => str_repeat('x', 2_000_000), 'published' => 0, 'template' => 0], JSON_THROW_ON_ERROR));
    $r = req('POST', "$base/api/v1/resources", array_merge($auth, ['Content-Type: application/json']), (string) file_get_contents($bigPath));
    @unlink($bigPath);
    check('limits.body_too_large', $r['status'] === 413, (string)$r['status'] . ' ' . substr($r['body'], 0, 80));
}

// --- API key ---
if ($apiKey !== '') {
    $r = req('GET', "$base/api/v1/contexts?limit=5", $auth);
    check('contexts.with_key', $r['status'] === 200, (string)$r['status'] . ' ' . substr($r['body'],0,120));

    $r = req('GET', "$base/api/v1/contexts/web/settings", $auth);
    check('contexts.settings', $r['status'] === 200 && is_array($r['json']['data'] ?? null), (string)$r['status']);

    $r = req('GET', "$base/api/v1/chunks?limit=1", $auth);
    check('chunks.with_key', $r['status'] === 200, (string)$r['status']);

    $r = req('GET', "$base/api/v1/templates?limit=1&fields=id,templatename", $auth);
    check('templates.with_key', $r['status'] === 200, (string)$r['status'] . ' ' . substr($r['body'],0,120));

    $r = req('GET', "$base/api/v1/tvs?limit=1&fields=id,name,type", $auth);
    check('tvs.with_key', $r['status'] === 200, (string)$r['status'] . ' ' . substr($r['body'],0,120));

    $r = req('GET', "$base/api/v1/snippets?limit=1&include=category&fields=id,name,category", $auth);
    check('snippets.include.category', $r['status'] === 200, (string)$r['status'] . ' ' . substr($r['body'],0,160));

    $r = req('GET', "$base/api/v1/templates?" . http_build_query(['limit' => 1, 'filter' => ['published' => ['eq' => 1]]]), $auth);
    check('templates.filter.published_denied', $r['status'] === 422, (string)$r['status']);

    $r = req('GET', "$base/api/v1/categories?" . http_build_query(['limit' => 1, 'filter' => ['parent' => ['eq' => 0]]]), $auth);
    check('categories.filter.parent', $r['status'] === 200, (string)$r['status']);

    $r = req('GET', "$base/api/v1/resources/1?fields=id,createdby", $auth);
    check(
        'resources.protected_fields_denied',
        $r['status'] === 422 && ($r['json']['code'] ?? '') === 'validation_failed',
        (string)$r['status'] . ' ' . substr($r['body'], 0, 120),
    );

    $r = req('GET', "$base/api/v1/resources?limit=1&fields=id", array_merge($auth, ['Accept: text/html']));
    check('accept.html_on_json_denied', $r['status'] === 406, (string)$r['status']);

    $r = req('GET', "$base/api/v1/docs", ['Accept: text/html']);
    check('accept.html_docs_ok', $r['status'] === 200 && str_contains($r['headers']['content-type'] ?? '', 'text/html'), (string)$r['status']);

    $r = req('GET', "$base/api/v1/contexts/web", $auth);
    check('contexts.get_by_key', $r['status'] === 200 && ($r['json']['data']['key'] ?? '') === 'web', (string)$r['status'] . ' ' . substr($r['body'], 0, 120));

    $r = req('GET', "$base/api/v1/resources?limit=2&page=2&offset=10&fields=id", $auth);
    check(
        'pagination.page_offset_conflict',
        $r['status'] === 422 && ($r['json']['code'] ?? '') === 'validation_failed',
        (string)$r['status'] . ' ' . substr($r['body'], 0, 160),
    );

    $r = req('GET', "$base/api/v1/meta/openapi.json", $auth);
    $oaTags = array_column($r['json']['tags'] ?? [], 'name');
    $tplInclude = false;
    foreach ($r['json']['paths']['/templates']['get']['parameters'] ?? [] as $p) {
        if (($p['name'] ?? '') === 'include' && ($p['schema']['example'] ?? null) === 'category') {
            $tplInclude = true;
        }
    }
    $ctParams = array_column($r['json']['paths']['/content_types']['get']['parameters'] ?? [], 'name');
    $hasContextsKey = isset($r['json']['paths']['/contexts/{key}']);
    $deleteParams = array_column($r['json']['paths']['/resources/{id}']['delete']['parameters'] ?? [], 'name');
    $getItemParams = array_column($r['json']['paths']['/resources/{id}']['get']['parameters'] ?? [], 'name');
    check(
        'openapi.element_includes',
        $r['status'] === 200
            && in_array('Templates', $oaTags, true)
            && $tplInclude
            && !in_array('include', $ctParams, true)
            && $hasContextsKey
            && in_array('force', $deleteParams, true)
            && in_array('include_deleted', $getItemParams, true),
        'tags=' . json_encode($oaTags) . ' tplInclude=' . ($tplInclude ? '1' : '0') . ' ct=' . json_encode($ctParams) . ' contextsKey=' . ($hasContextsKey ? '1' : '0') . ' del=' . json_encode($deleteParams) . ' get=' . json_encode($getItemParams),
    );

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

        $r = req('DELETE', "$base/api/v1/resources/$createdId", $auth);
        check('resources.delete_twice_404', $r['status'] === 404, (string)$r['status'] . ' ' . substr($r['body'],0,120));
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

// --- assets/api.php fallback (nginx-friendly ?route=) ---
$r = req('GET', "$base/assets/components/mxheadless/api.php");
check(
    'fallback.api_php.discovery',
    $r['status'] === 200 && ($r['json']['data']['name'] ?? '') === 'mxHeadless',
    (string)$r['status'] . ' ' . substr($r['body'], 0, 120)
);

$r = req('GET', "$base/assets/components/mxheadless/api.php?route=/v1/health");
check(
    'fallback.api_php.route_health',
    $r['status'] === 200 && (($r['json']['data']['status'] ?? '') === 'ok' || ($r['json']['status'] ?? '') === 'ok'),
    (string)$r['status'] . ' ' . substr($r['body'], 0, 120)
);

echo "\n$pass passed, $fail failed\n";
if ($bugs) {
    echo "Bugs:\n- " . implode("\n- ", $bugs) . "\n";
}
exit($fail > 0 ? 1 : 0);
