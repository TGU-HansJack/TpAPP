<?php

/**
 * Typecho REST API Plugin bootstrap file.
 */

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

if (!defined('TYPECHO_API_PLUGIN_ROOT')) {
    define('TYPECHO_API_PLUGIN_ROOT', __DIR__);
}

require_once __DIR__ . '/src/Bootstrap.php';

use TypechoApiPlugin\Bootstrap;
use TypechoApiPlugin\Support\PluginOptions;

trait TypechoApiPlugin_PluginMethods
{
    public static function activate()
    {
        Bootstrap::init();
        return _t('Typecho API 插件已经启用，REST 服务入口准备就绪。');
    }

    public static function deactivate()
    {
        Bootstrap::shutdown();
    }

    public static function config($form)
    {
        Bootstrap::ensureAutoloader();
        PluginOptions::buildForm($form);
    }

    public static function personalConfig($form)
    {
        // no-op
    }

    public static function render()
    {
    }
}

if (interface_exists('\Typecho\Plugin\PluginInterface')) {
    class typecho_api_plugin_Plugin implements \Typecho\Plugin\PluginInterface
    {
        use TypechoApiPlugin_PluginMethods;
    }
} else {
    class typecho_api_plugin_Plugin implements \Typecho_Plugin_Interface
    {
        use TypechoApiPlugin_PluginMethods;
    }
}

if (!class_exists('TypechoApiPlugin_Plugin', false)) {
    class TypechoApiPlugin_Plugin extends typecho_api_plugin_Plugin
    {
    }
}
