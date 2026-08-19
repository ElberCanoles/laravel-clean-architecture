<?php

namespace CleanArchitecture\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseGenerator extends Command
{
    /** Identifier strategies supported by the generators. */
    protected const ID_TYPES = ['uuid', 'ulid'];

    /**
     * PHP keywords and reserved type names that cannot be used as class names.
     */
    protected const RESERVED_NAMES = [
        'abstract', 'and', 'array', 'as', 'break', 'callable', 'case', 'catch',
        'class', 'clone', 'const', 'continue', 'declare', 'default', 'do',
        'echo', 'else', 'elseif', 'empty', 'enddeclare', 'endfor', 'endforeach',
        'endif', 'endswitch', 'endwhile', 'enum', 'exit', 'extends', 'final',
        'finally', 'fn', 'for', 'foreach', 'function', 'global', 'goto', 'if',
        'implements', 'include', 'instanceof', 'insteadof', 'interface',
        'isset', 'list', 'match', 'namespace', 'new', 'or', 'print', 'private',
        'protected', 'public', 'readonly', 'require', 'return', 'static',
        'switch', 'throw', 'trait', 'try', 'unset', 'use', 'var', 'while',
        'xor', 'yield',
        // Reserved type names
        'bool', 'false', 'float', 'int', 'iterable', 'mixed', 'never', 'null',
        'object', 'parent', 'self', 'string', 'true', 'void',
    ];

    /**
     * Translate generator exceptions into clean console errors and exit codes
     * instead of uncaught stack traces.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            return parent::execute($input, $output);
        } catch (\InvalidArgumentException $e) {
            $this->components->error($e->getMessage());

            return self::INVALID;
        } catch (\RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }
    }

    protected function getStub(string $name): string
    {
        $customPath = base_path("stubs/clean-architecture/$name.stub");

        if (File::exists($customPath)) {
            return File::get($customPath);
        }

        $packagePath = __DIR__ . "/../../stubs/$name.stub";

        if (! File::exists($packagePath)) {
            throw new \RuntimeException(
                "Stub '$name.stub' not found. Looked in:\n"
                . "  - $customPath\n"
                . "  - $packagePath\n"
                . 'Run: php artisan vendor:publish --tag=clean-architecture-stubs'
            );
        }

        return File::get($packagePath);
    }

    protected function getNamespacePrefix(): string
    {
        return config('clean-architecture.namespace_prefix', 'Src');
    }

    protected function buildNamespace(string $context): string
    {
        return $this->getNamespacePrefix() . "\\$context";
    }

    /**
     * Validate that a context or class name is a valid PHP identifier (PascalCase).
     */
    protected function validateName(string $value, string $label): void
    {
        if (! preg_match('/^[A-Z][a-zA-Z0-9]*$/', $value)) {
            throw new \InvalidArgumentException(
                "Invalid $label: '$value'. Must start with an uppercase letter and contain only alphanumeric characters (e.g. 'Billing', 'Invoice')."
            );
        }

        if (in_array(strtolower($value), self::RESERVED_NAMES, true)) {
            throw new \InvalidArgumentException(
                "Invalid $label: '$value' is a PHP reserved word and cannot be used as a class name."
            );
        }
    }

    /**
     * Resolve the identifier strategy: --id-type option first, then config, then 'uuid'.
     */
    protected function resolveIdType(): string
    {
        $idType = ($this->hasOption('id-type') ? $this->option('id-type') : null)
            ?: config('clean-architecture.id_type', 'uuid');

        if (! in_array($idType, self::ID_TYPES, true)) {
            throw new \InvalidArgumentException(
                "Invalid id type: '$idType'. Must be " . $this->humanizeIdTypes() . '.'
            );
        }

        return $idType;
    }

    /**
     * The Eloquent concern that generates keys for the given identifier strategy.
     */
    protected function idTrait(string $idType): string
    {
        return $idType === 'ulid' ? 'HasUlids' : 'HasUuids';
    }

    /**
     * The Str expression that produces a new identifier, ready to be embedded in generated code.
     */
    protected function idFactoryCall(string $idType): string
    {
        return $idType === 'ulid' ? 'Str::ulid()' : 'Str::uuid7()';
    }

    protected function humanizeIdTypes(): string
    {
        return "'" . implode("' or '", self::ID_TYPES) . "'";
    }

    /**
     * Stubs published before ULID support hardcode the identifier, so the placeholder
     * is missing and the resolved id type would be dropped without a trace.
     */
    protected function warnIfStubIgnoresIdType(string $stub, string $stubName, string $placeholder): void
    {
        if (str_contains($stub, $placeholder)) {
            return;
        }

        $this->warn(
            "Custom stub '$stubName.stub' has no $placeholder placeholder — the id type was not applied. "
            . 'Re-publish stubs with: php artisan vendor:publish --tag=clean-architecture-stubs --force'
        );
    }

    protected function toPluralStudly(string $name): string
    {
        return Str::plural($name);
    }

    protected function toKebab(string $name): string
    {
        return strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $name));
    }

    protected function toKebabPlural(string $name): string
    {
        return Str::plural($this->toKebab($name));
    }

    protected function writeFile(string $filePath, string $content): bool
    {
        if (File::exists($filePath) && ! $this->option('force')) {
            $this->warn("File already exists (use --force to overwrite): $filePath");

            return false;
        }

        if (File::put($filePath, $content) === false) {
            $this->components->error("Could not write file: $filePath");

            return false;
        }

        return true;
    }
}
