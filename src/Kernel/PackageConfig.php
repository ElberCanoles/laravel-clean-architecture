<?php

declare(strict_types=1);

namespace CleanArchitecture\Kernel;

/**
 * Typed access to the package configuration. Every default lives here, so an
 * unpublished config file (or a key set to the wrong type) resolves the same
 * way in the generators, the module loader, and the service provider.
 */
final class PackageConfig
{
    public static function contextsPath(): string
    {
        return self::string('contexts_path', 'src');
    }

    public static function namespacePrefix(): string
    {
        return self::string('namespace_prefix', 'Src');
    }

    public static function idType(): string
    {
        return self::string('id_type', 'uuid');
    }

    public static function stubsPath(): string
    {
        return self::string('stubs_path', 'stubs/clean-architecture');
    }

    public static function archTestsPath(): string
    {
        return self::string('arch_tests_path', 'tests/Feature/Architecture');
    }

    public static function unitTestsPath(): string
    {
        return self::string('unit_tests_path', 'tests/Unit/Domain');
    }

    public static function handlerTestsPath(): string
    {
        return self::string('handler_tests_path', 'tests/Unit/Application');
    }

    public static function featureTestsPath(): string
    {
        return self::string('feature_tests_path', 'tests/Feature');
    }

    public static function autoLoad(): bool
    {
        return self::bool('auto_load', true);
    }

    public static function autoDiscover(): bool
    {
        return self::bool('auto_discover', true);
    }

    public static function rendersDomainExceptions(): bool
    {
        return self::bool('render_domain_exceptions', true);
    }

    private static function string(string $key, string $default): string
    {
        $value = config("clean-architecture.$key", $default);

        return is_string($value) ? $value : $default;
    }

    private static function bool(string $key, bool $default): bool
    {
        $value = config("clean-architecture.$key", $default);

        if (is_bool($value)) {
            return $value;
        }

        // Strings such as "0"/"false" from env() keep their truthiness.
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
