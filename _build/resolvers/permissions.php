<?php

/**
 * Ensure mxheadless_apikeys exists on the Administrator policy.
 *
 * @var \xPDO\Transport\xPDOTransport $transport
 * @var array $options
 * @var \MODX\Revolution\modX $modx
 */

use MODX\Revolution\modAccessPolicy;
use MODX\Revolution\modAccessPolicyTemplate;
use MODX\Revolution\modAccessPermission;

if ($options[xPDOTransport::PACKAGE_ACTION] === xPDOTransport::ACTION_UNINSTALL) {
    return true;
}

/** @var modAccessPolicy|null $policy */
$policy = $modx->getObject(modAccessPolicy::class, ['name' => 'Administrator']);
if ($policy) {
    $data = $policy->get('data');
    if (!is_array($data)) {
        $data = [];
    }
    if (empty($data['mxheadless_apikeys'])) {
        $data['mxheadless_apikeys'] = true;
        $policy->set('data', $data);
        $policy->save();
    }
}

/** @var modAccessPolicyTemplate|null $template */
$template = $modx->getObject(modAccessPolicyTemplate::class, ['name' => 'AdministratorTemplate']);
if ($template) {
    $exists = $modx->getObject(modAccessPermission::class, [
        'template' => $template->get('id'),
        'name' => 'mxheadless_apikeys',
    ]);
    if (!$exists) {
        $permission = $modx->newObject(modAccessPermission::class);
        $permission->fromArray([
            'template' => $template->get('id'),
            'name' => 'mxheadless_apikeys',
            'description' => 'Manage mxHeadless API keys',
            'value' => 1,
        ]);
        $permission->save();
    }
}

return true;
