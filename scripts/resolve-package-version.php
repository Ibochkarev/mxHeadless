<?php

declare(strict_types=1);

/** @var array<string, mixed> $config */
$config = require dirname(__DIR__) . '/_build/config.inc.php';

echo $config['version'] . '-' . $config['release'] . PHP_EOL;
