<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$payload = file_get_contents('php://input') ?: '';
$record = [
    'received_at' => gmdate('c'),
    'event' => $_SERVER['HTTP_X_MXHEADLESS_EVENT'] ?? '',
    'delivery_id' => $_SERVER['HTTP_X_MXHEADLESS_DELIVERY_ID'] ?? '',
    'signature' => $_SERVER['HTTP_X_MXHEADLESS_SIGNATURE'] ?? '',
    'payload' => $payload,
];

$dir = sys_get_temp_dir() . '/mxheadless-webhooks';
if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'cannot create catcher dir']);
    exit;
}

$file = $dir . '/latest.json';
file_put_contents($file, json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

http_response_code(200);
echo json_encode(['ok' => true]);
