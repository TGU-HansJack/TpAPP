<?php

namespace TypechoApiPlugin;

use TypechoApiPlugin\Extensions\ExtensionManager;
use TypechoApiPlugin\Http\Kernel;
use TypechoApiPlugin\Support\Compatibility;
use TypechoApiPlugin\Support\PluginOptions;

class Bootstrap
{
    private static bool $autoloaderRegistered = false;
    private static bool $bootstrapped = false;

    public static function ensureAutoloader(): void
    {
        if (self::$autoloaderRegistered) {
            return;
        }

        spl_autoload_register(function ($class) {
            if (0 !== strpos($class, __NAMESPACE__ . '\\')) {
                return;
            }

            $relative = substr($class, strlen(__NAMESPACE__) + 1);
            $path = TYPECHO_API_PLUGIN_ROOT . '/src/' . str_replace('\\', '/', $relative) . '.php';

            if (is_file($path)) {
                require_once $path;
            }
        });

        self::$autoloaderRegistered = true;
    }

    public static function init(): void
    {
        self::ensureAutoloader();

        if (self::$bootstrapped) {
            return;
        }

        ExtensionManager::boot();
        self::registerHooks();
        self::$bootstrapped = true;
    }

    public static function shutdown(): void
    {
        ExtensionManager::reset();
        PluginOptions::resetInstance();
    }

    private static function registerHooks(): void
    {
        $archiveHandle = Compatibility::widgetHandle('Widget_Archive');
        $factory = Compatibility::pluginFactory($archiveHandle);
        $factory->begin = [Kernel::class, 'bootstrap'];
    }
}
