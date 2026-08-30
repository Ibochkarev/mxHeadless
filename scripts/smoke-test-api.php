#!/usr/bin/env php
<?php

declare(strict_types=1);

$base = rtrim($argv[1] ?? 'https://project.test', '/');
$apiKey = $argv[2] ?? null;

$cases = [
    ['method' => 'GET', 'path' => '/api/v1', 'expect' => 200],
    ['method' => 'GET', 'path' => '/api/v1/health', 'expect' => 200],
    ['method' => 'GET', 'path' => '/api/v1/meta/endpoints', 'expect' => 200],
    ['method' => 'GET', 'path' => '/api/v1/meta/openapi', 'expect' => 200],
    ['method' => 'GET', 'path' => '/api/v1/meta/openapi.json', 'expect' => 200],
    ['method' => 'GET', 'path' => '/api/v1/docs', 'expect' => 200],
    ['method' => 'GET', 'path' => '/api/v1/schema', 'expect' => 200],
    ['method' => 'GET', 'path' => '/api/v1/resources?limit=1&filter[published]=1', 'expect' => 200],
    ['method' => 'GET', 'path' => '/api/v1/contexts?limit=1', 'expect' => 401],
    ['method' => 'POST', 'path' => '/api/v1/auth/token', 'expect' => 422],
    ['method' => 'OPTIONS', 'path' => '/api/v1/resources', 'expect' => 204],
];

$passed = 0;
$failed = 0;

foreach ($cases as $case) {
    $url = $base . $case['path'];
    $headers = ['Accept: application/json'];
    if ($apiKey !== null && !empty($case['withKey'])) {
        $headers[] = 'Authorization: Bearer ' . $apiKey;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $case['method'],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $ok = $status === $case['expect'];
    echo ($ok ? 'OK  ' : 'FAIL') . " {$case['method']} {$case['path']} -> {$status} (expected {$case['expect']})\n";
    $ok ? ++$passed : ++$failed;
}

if ($apiKey !== null) {
    $ch = curl_init($base . '/api/v1/contexts?limit=1');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $apiKey],
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $ok = $status === 200;
    echo ($ok ? 'OK  ' : 'FAIL') . " GET /api/v1/contexts?limit=1 (auth) -> {$status} (expected 200)\n";
    $ok ? ++$passed : ++$failed;
}

echo "\n{$passed} passed, {$failed} failed\n";
exit($failed > 0 ? 1 : 0);
