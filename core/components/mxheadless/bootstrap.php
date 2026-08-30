<?php

/**
 * @var \MODX\Revolution\modX $modx
 * @var array{path?: string}|null $namespace
 */

use MxHeadless\ApplicationFactory;

$componentPath = (is_array($namespace ?? null) && isset($namespace['path']) && is_string($namespace['path']))
    ? $namespace['path']
    : (__DIR__ . '/');
$componentPath = rtrim($componentPath, '/\\') . '/';
$vendorPath = $componentPath . 'vendor/';

if (file_exists($vendorPath . 'autoload.php')) {
    require_once $vendorPath . 'autoload.php';
}

$modx->addPackage('MxHeadless\\Model', $componentPath . 'src/Model/', null, 'MxHeadless\\');

if (!$modx->services->has(\Psr\Http\Client\ClientInterface::class)) {
    $modx->services->add(
        \Psr\Http\Client\ClientInterface::class,
        static function () use ($modx): \Psr\Http\Client\ClientInterface {
            // Dev-only: when private webhook URLs are allowed, skip TLS verify for local certs.
            $verifyPeer = !(bool) $modx->getOption('mxheadless.webhook.allow_private_urls', null, false);

            return new \MxHeadless\Http\CurlHttpClient($verifyPeer);
        },
    );
}

$modx->services->add(ApplicationFactory::class, static function () use ($modx): ApplicationFactory {
    return new ApplicationFactory($modx);
});

$modx->services->add('mxheadless', static function ($c) {
    /** @var ApplicationFactory $factory */
    $factory = $c->get(ApplicationFactory::class);

    return $factory->create();
});
