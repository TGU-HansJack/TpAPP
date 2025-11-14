<?php

namespace TypechoApiPlugin\Support;

use TypechoApiPlugin\Exceptions\ApiException;

class Compatibility
{
    public static function pluginFactory(string $handle)
    {
        if (class_exists('\Typecho\Plugin')) {
            return \Typecho\Plugin::factory($handle);
        }

        return \Typecho_Plugin::factory($handle);
    }

    public static function widgetHandle(string $handle): string
    {
        $namespaced = '\\' . str_replace('_', '\\', ltrim($handle, '\\'));
        if (class_exists($namespaced)) {
            return $namespaced;
        }

        return $handle;
    }

    public static function widget(string $handle, $params = null, $request = null, $callback = true)
    {
        if (class_exists('\Widget')) {
            return \Widget::widget($handle, $params, $request, $callback);
        }

        return \Widget::widget($handle, $params, $request, $callback);
    }

    public static function request()
    {
        if (class_exists('\Typecho\Request')) {
            return \Typecho\Request::getInstance();
        }

        return \Typecho_Request::getInstance();
    }

    public static function response()
    {
        if (class_exists('\Typecho\Response')) {
            return \Typecho\Response::getInstance();
        }

        return \Typecho_Response::getInstance();
    }

    public static function db()
    {
        if (class_exists('\Typecho\Db')) {
            return \Typecho\Db::get();
        }

        return \Typecho_Db::get();
    }

    public static function options()
    {
        $handle = class_exists('\Widget\Options') ? '\Widget\Options' : 'Widget_Options';
        return self::widget($handle);
    }

    public static function user()
    {
        $handle = class_exists('\Widget\User') ? '\Widget\User' : 'Widget_User';
        return self::widget($handle);
    }

    public static function pluginConfig(string $pluginName)
    {
        $options = self::options();
        if (method_exists($options, 'plugin')) {
            return $options->plugin($pluginName);
        }

        if (isset($options->plugins[$pluginName])) {
            return $options->plugins[$pluginName];
        }

        return null;
    }

    public static function rootDir(): string
    {
        return defined('__TYPECHO_ROOT_DIR__') ? __TYPECHO_ROOT_DIR__ : dirname(__DIR__, 5);
    }

    public static function hashValidate(?string $source, ?string $hash): bool
    {
        if (class_exists('\Typecho\Common')) {
            return \Typecho\Common::hashValidate($source, $hash);
        }

        return \Typecho_Common::hashValidate($source, $hash);
    }

    public static function hash(?string $value, ?string $salt = null): string
    {
        if (class_exists('\Typecho\Common')) {
            return \Typecho\Common::hash($value, $salt);
        }

        return \Typecho_Common::hash($value, $salt);
    }

    public static function randomString(int $length = 32): string
    {
        $bytes = random_bytes(max(1, $length));
        return substr(bin2hex($bytes), 0, $length);
    }

    public static function throwApiError(string $message, int $code = 400): void
    {
        throw new ApiException($message, $code);
    }
}
