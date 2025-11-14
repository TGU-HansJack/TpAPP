<?php

namespace TypechoApiPlugin\Support;

class PluginOptions
{
    private array $values;
    private static ?self $instance = null;

    public function __construct(?array $values = null)
    {
        if (null === $values) {
            $values = self::readFromTypecho();
        }

        $this->values = $values;
    }

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public function get(string $key, $default = null)
    {
        return $this->values[$key] ?? $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key);
        if (null === $value) {
            return $default;
        }

        return in_array($value, [true, 1, '1', 'true', 'on'], true);
    }

    public function prefix(): string
    {
        $prefix = trim($this->get('api_prefix', '/api/v1'));
        return '/' . trim($prefix, '/');
    }

    public function tokenSecret(): string
    {
        return (string) $this->get('token_secret', Compatibility::randomString(48));
    }

    public function tokenTtl(): int
    {
        return (int) $this->get('token_ttl', 86400);
    }

    public function signatureSecret(): string
    {
        return (string) $this->get('signature_secret', $this->tokenSecret());
    }

    public function rateLimitRequests(): int
    {
        return (int) $this->get('rate_limit_requests', 120);
    }

    public function rateLimitInterval(): int
    {
        return (int) $this->get('rate_limit_interval', 60);
    }

    public function sensitiveWords(): array
    {
        $string = (string) $this->get('comment_sensitive_words', '');
        $lines = preg_split('/[\r\n]+/', $string);
        $filtered = array_filter(array_map('trim', $lines));
        return array_values(array_unique($filtered));
    }

    public function blacklist(): array
    {
        $string = (string) $this->get('ip_blacklist', '');
        $lines = preg_split('/[\r\n]+/', $string);
        $filtered = array_filter(array_map('trim', $lines));
        return array_values($filtered);
    }

    public function all(): array
    {
        return $this->values;
    }

    private static function readFromTypecho(): array
    {
        $config = Compatibility::pluginConfig('typecho_api_plugin');

        if (!$config) {
            return [];
        }

        if (method_exists($config, 'toArray')) {
            return $config->toArray();
        }

        return (array) $config;
    }

    public static function buildForm($form): void
    {
        $text = self::element('Text');
        $textarea = self::element('Textarea');
        $radio = self::element('Radio');

        $form->addInput(new $text(
            'api_prefix',
            null,
            '/api/v1',
            _t('API 前缀'),
            _t('默认 /api/v1，所有接口将以该路径为基础。')
        ));

        $form->addInput(new $text(
            'token_secret',
            null,
            Compatibility::randomString(48),
            _t('Token 密钥'),
            _t('用于生成 API Token 的密钥，请勿泄漏。')
        ));

        $form->addInput(new $text(
            'token_ttl',
            null,
            '86400',
            _t('Token 有效期(秒)'),
            _t('默认 86400 秒 (24h)。')
        ));

        $form->addInput(new $radio(
            'allow_cors',
            [1 => _t('开启'), 0 => _t('关闭')],
            1,
            _t('CORS 跨域'),
            _t('是否自动输出 CORS 相关响应头。')
        ));

        $form->addInput(new $text(
            'cors_origins',
            null,
            '*',
            _t('允许跨域的 Origin'),
            _t('多个域名使用逗号分隔，默认为 *。')
        ));

        $form->addInput(new $radio(
            'enable_signature',
            [1 => _t('开启'), 0 => _t('关闭')],
            0,
            _t('签名模式'),
            _t('开启后校验 timestamp + nonce + signature。')
        ));

        $form->addInput(new $text(
            'signature_secret',
            null,
            Compatibility::randomString(48),
            _t('签名密钥'),
            _t('签名模式使用的密钥。')
        ));

        $form->addInput(new $radio(
            'rate_limit_enabled',
            [1 => _t('开启'), 0 => _t('关闭')],
            1,
            _t('限流'),
            _t('基于 IP 的访问频率限制。')
        ));

        $form->addInput(new $text(
            'rate_limit_requests',
            null,
            '120',
            _t('限流次数'),
            _t('在时间窗口内允许的最大请求数。')
        ));

        $form->addInput(new $text(
            'rate_limit_interval',
            null,
            '60',
            _t('限流时间窗口(秒)'),
            _t('默认 60 秒。')
        ));

        $form->addInput(new $radio(
            'log_requests',
            [1 => _t('开启'), 0 => _t('关闭')],
            1,
            _t('记录日志'),
            _t('记录 API 请求/错误日志。')
        ));

        $form->addInput(new $radio(
            'comment_require_login',
            [1 => _t('是'), 0 => _t('否')],
            0,
            _t('评论需登录'),
            _t('是否强制登录用户才可发表评论。')
        ));

        $form->addInput(new $textarea(
            'comment_sensitive_words',
            null,
            '',
            _t('评论敏感词'),
            _t('每行一个关键词，命中后评论将被拒绝。')
        ));

        $form->addInput(new $textarea(
            'ip_blacklist',
            null,
            '',
            _t('IP 黑名单'),
            _t('每行一个 IP，位于名单中的请求直接拒绝。')
        ));
    }

    private static function element(string $name): string
    {
        $namespaced = '\\Typecho\\Widget\\Helper\\Form\\Element\\' . $name;
        $legacy = 'Typecho_Widget_Helper_Form_Element_' . $name;
        if (class_exists($namespaced)) {
            return $namespaced;
        }

        return $legacy;
    }
}
