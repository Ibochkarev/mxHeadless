<?php

/**
 * @var xPDOTransportVehicle $vehicle
 * @var array<string, mixed> $options
 * @var modX $modx
 */

if (!$modx instanceof modX) {
    return false;
}

$events = [
    'OnMxHeadlessRegister' => [
        'service' => 6,
        'groupname' => 'mxHeadless',
    ],
    'OnMxHeadlessRegisterMiddleware' => [
        'service' => 6,
        'groupname' => 'mxHeadless',
    ],
    'OnMxHeadlessBeforeRequest' => [
        'service' => 6,
        'groupname' => 'mxHeadless',
    ],
    'OnMxHeadlessAfterRequest' => [
        'service' => 6,
        'groupname' => 'mxHeadless',
    ],
];

foreach ($events as $name => $data) {
    /** @var modEvent|null $event */
    $event = $modx->getObject('modEvent', ['name' => $name]);
    if ($event !== null) {
        continue;
    }

    $event = $modx->newObject('modEvent');
    if ($event === null) {
        continue;
    }

    $event->fromArray(array_merge(['name' => $name], $data), '', true, true);
    $event->save();
}

/** @var modPlugin|null $plugin */
$plugin = $modx->getObject('modPlugin', ['name' => 'mxHeadless']);
if ($plugin !== null) {
    $legacy = $modx->getObject('modPluginEvent', [
        'pluginid' => $plugin->get('id'),
        'event' => 'OnMxHeadlessRegister',
    ]);
    if ($legacy !== null) {
        $legacy->remove();
    }
}

return true;
