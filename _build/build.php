<?php

use MODX\Revolution\modCategory;
use MODX\Revolution\modMenu;
use MODX\Revolution\modPlugin;
use MODX\Revolution\modPluginEvent;
use MODX\Revolution\modSystemSetting;
use MODX\Revolution\modX;
use MODX\Revolution\Transport\modPackageBuilder;
use MODX\Revolution\Transport\modTransportPackage;

class mxHeadlessPackage
{
    private modX $modx;

    /** @var array<string, mixed> */
    private array $config = [];

    private modCategory $category;

    /** @var array<string, mixed> */
    private array $categoryAttributes = [];

    public modPackageBuilder $builder;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(modX $modx, array $config = [])
    {
        $this->modx = $modx;
        $this->modx->initialize('mgr');

        $root = dirname(__FILE__, 2) . '/';
        $nameLower = (string) ($config['name_lower'] ?? 'mxheadless');

        $this->config = array_merge([
            'log_level' => modX::LOG_LEVEL_INFO,
            'log_target' => XPDO_CLI_MODE ? 'ECHO' : 'HTML',
            'root' => $root,
            'build' => $root . '_build/',
            'elements' => $root . '_build/elements/',
            'resolvers' => $root . '_build/resolvers/',
            'core' => $root . 'core/components/' . $nameLower . '/',
            'assets' => $root . 'assets/components/' . $nameLower . '/',
        ], $config);

        $this->modx->setLogLevel((int) $this->config['log_level']);
        $this->modx->setLogTarget((string) $this->config['log_target']);

        $this->initialize();
    }

    public function process(): modPackageBuilder
    {
        $this->runComposerInstall();
        $this->plugins();
        $this->settings();
        $this->menus();

        $vehicle = $this->builder->createVehicle($this->category, $this->categoryAttributes);

        $vehicle->resolve('file', [
            'source' => $this->config['core'],
            'target' => "return MODX_CORE_PATH . 'components/';",
        ]);
        $vehicle->resolve('file', [
            'source' => $this->config['assets'],
            'target' => "return MODX_ASSETS_PATH . 'components/';",
        ]);

        $resolverDir = (string) $this->config['resolvers'];
        foreach (['tables.php', 'events.php', 'permissions.php', 'settings-rename.php'] as $resolver) {
            $source = $resolverDir . $resolver;
            if (is_file($source) && $vehicle->resolve('php', ['source' => $source])) {
                $this->modx->log(modX::LOG_LEVEL_INFO, 'Added resolver ' . preg_replace('#\.php$#', '', $resolver));
            }
        }

        $this->builder->putVehicle($vehicle);

        $this->builder->setPackageAttributes([
            'changelog' => $this->readDoc('changelog.txt'),
            'license' => $this->readDoc('license.txt'),
            'readme' => $this->readDoc('readme.txt'),
            'requires' => [
                'php' => '>=8.1.0',
                'modx' => '>=3.0.0',
            ],
        ]);

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packing transport package...');
        $this->builder->pack();

        if (!empty($this->config['install'])) {
            $this->install();
        }

        return $this->builder;
    }

    private function initialize(): void
    {
        $this->builder = new modPackageBuilder($this->modx);
        $this->builder->createPackage(
            (string) $this->config['name_lower'],
            (string) $this->config['version'],
            (string) $this->config['release']
        );
        $this->builder->registerNamespace(
            (string) $this->config['name_lower'],
            false,
            true,
            '{core_path}components/' . $this->config['name_lower'] . '/',
            '{assets_path}components/' . $this->config['name_lower'] . '/',
        );

        $this->category = $this->modx->newObject(modCategory::class);
        $this->category->set('category', (string) $this->config['name']);
        $this->categoryAttributes = [
            xPDOTransport::UNIQUE_KEY => 'category',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [],
        ];
    }

    private function runComposerInstall(): void
    {
        $composerJson = (string) $this->config['core'] . 'composer.json';
        if (!is_file($composerJson)) {
            return;
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            ['composer', 'install', '--no-dev', '--no-interaction'],
            $descriptorSpec,
            $pipes,
            (string) $this->config['core']
        );

        if (!is_resource($process)) {
            $this->modx->log(modX::LOG_LEVEL_WARN, 'Composer install skipped: could not start process.');

            return;
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        proc_close($process);

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Composer install: ' . trim($stdout . "\n" . $stderr));
    }

    private function settings(): void
    {
        $settings = include (string) $this->config['elements'] . 'settings.php';
        if (!is_array($settings)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package System Settings');

            return;
        }

        $attributes = [
            xPDOTransport::UNIQUE_KEY => 'key',
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['settings']),
            xPDOTransport::RELATED_OBJECTS => false,
        ];

        foreach ($settings as $name => $data) {
            if (!is_array($data)) {
                continue;
            }

            /** @var modSystemSetting $setting */
            $setting = $this->modx->newObject(modSystemSetting::class);
            $setting->fromArray(array_merge([
                'key' => (string) $this->config['name_lower'] . '_' . str_replace('.', '_', (string) $name),
                'namespace' => (string) $this->config['name_lower'],
            ], $data), '', true, true);

            $vehicle = $this->builder->createVehicle($setting, $attributes);
            $this->builder->putVehicle($vehicle);
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($settings) . ' System Settings');
    }

    private function menus(): void
    {
        $menus = include (string) $this->config['elements'] . 'menus.php';
        if (!is_array($menus)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package Menus');

            return;
        }

        $attributes = [
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => 'text',
            xPDOTransport::RELATED_OBJECTS => false,
        ];

        foreach ($menus as $name => $data) {
            if (!is_array($data)) {
                continue;
            }

            /** @var modMenu $menu */
            $menu = $this->modx->newObject(modMenu::class);
            $menu->fromArray(array_merge([
                'text' => $name,
                'parent' => 'components',
                'namespace' => (string) $this->config['name_lower'],
            ], $data), '', true, true);

            $vehicle = $this->builder->createVehicle($menu, $attributes);
            $this->builder->putVehicle($vehicle);
        }

        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($menus) . ' Menus');
    }

    private function plugins(): void
    {
        $plugins = include (string) $this->config['elements'] . 'plugins.php';
        if (!is_array($plugins)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Could not package Plugins');

            return;
        }

        $this->categoryAttributes[xPDOTransport::RELATED_OBJECT_ATTRIBUTES]['Plugins'] = [
            xPDOTransport::UNIQUE_KEY => 'name',
            xPDOTransport::PRESERVE_KEYS => false,
            xPDOTransport::UPDATE_OBJECT => !empty($this->config['update']['plugins']),
            xPDOTransport::RELATED_OBJECTS => true,
            xPDOTransport::RELATED_OBJECT_ATTRIBUTES => [
                'PluginEvents' => [
                    xPDOTransport::PRESERVE_KEYS => true,
                    xPDOTransport::UPDATE_OBJECT => true,
                    xPDOTransport::UNIQUE_KEY => ['pluginid', 'event'],
                ],
            ],
        ];

        $objects = [];
        foreach ($plugins as $name => $data) {
            if (!is_array($data)) {
                continue;
            }

            /** @var modPlugin $plugin */
            $plugin = $this->modx->newObject(modPlugin::class);
            $plugin->fromArray(array_merge([
                'name' => $name,
                'category' => 0,
                'description' => $data['description'] ?? '',
                'plugincode' => $this->getFileContent((string) $this->config['core'] . 'elements/plugins/' . $data['file'] . '.php'),
                'static' => !empty($this->config['static']['plugins']),
                'source' => 1,
                'static_file' => 'core/components/' . $this->config['name_lower'] . '/elements/plugins/' . $data['file'] . '.php',
            ], $data), '', true, true);

            $events = [];
            foreach ($data['events'] ?? [] as $eventName => $eventData) {
                if (!is_array($eventData)) {
                    $eventData = [];
                }

                /** @var modPluginEvent $event */
                $event = $this->modx->newObject(modPluginEvent::class);
                $event->fromArray(array_merge([
                    'event' => $eventName,
                    'priority' => 0,
                    'propertyset' => 0,
                ], $eventData), '', true, true);
                $events[] = $event;
            }

            if ($events !== []) {
                $plugin->addMany($events);
            }

            $objects[] = $plugin;
        }

        $this->category->addMany($objects);
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaged in ' . count($objects) . ' Plugins');
    }

    private function install(): void
    {
        $signature = $this->builder->getSignature();
        $sig = explode('-', $signature);
        $versionSignature = explode('.', $sig[1]);

        /** @var modTransportPackage|null $package */
        $package = $this->modx->getObject(modTransportPackage::class, ['signature' => $signature]);
        if (!$package) {
            $package = $this->modx->newObject(modTransportPackage::class);
            $package->set('signature', $signature);
            $package->fromArray([
                'created' => date('Y-m-d H:i:s'),
                'updated' => null,
                'state' => 1,
                'workspace' => 1,
                'provider' => 0,
                'source' => $signature . '.transport.zip',
                'package_name' => (string) $this->config['name'],
                'version_major' => $versionSignature[0],
                'version_minor' => !empty($versionSignature[1]) ? $versionSignature[1] : 0,
                'version_patch' => !empty($versionSignature[2]) ? $versionSignature[2] : 0,
            ]);
            $package->save();
        }

        if ($package->install()) {
            $this->modx->runProcessor('System/ClearCache');
        }
    }

    private function getFileContent(string $filename): string
    {
        if (!is_file($filename)) {
            return '';
        }

        $file = trim((string) file_get_contents($filename));

        return preg_match('#<\?php(.*)#is', $file, $data)
            ? rtrim(rtrim(trim($data[1]), '?>'))
            : $file;
    }

    private function readDoc(string $filename): string
    {
        $path = (string) $this->config['core'] . 'docs/' . $filename;
        if (!is_file($path)) {
            return '';
        }

        return trim((string) file_get_contents($path));
    }
}

/** @var array<string, mixed> $config */
if (!is_file(dirname(__FILE__) . '/config.inc.php')) {
    exit('Could not load MODX config. Please specify correct MODX_CORE_PATH constant in config file!');
}

$config = require dirname(__FILE__) . '/config.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$install = new mxHeadlessPackage($modx, $config);
$builder = $install->process();

if (!empty($config['download'])) {
    $name = $builder->getSignature() . '.transport.zip';
    $content = file_get_contents(MODX_CORE_PATH . '/packages/' . $name);
    if ($content !== false) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . $name);
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($content));
        exit($content);
    }
}
