<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

/**
 * Base for generators that render one stub into one file inside a context.
 * Subclasses only declare where the artifact lives, which stub renders it,
 * the class-name suffix, and the label used in console output.
 */
abstract class SingleFileGenerator extends BaseGenerator
{
    /** Directory under the context, e.g. 'Domain/Entities'. */
    abstract protected function subPath(): string;

    /** Stub name without extension, e.g. 'entity'. */
    abstract protected function stubName(): string;

    /** Label for console output, e.g. 'Entity'. */
    abstract protected function label(): string;

    /** Class-name suffix the stub appends, e.g. 'Event' — empty when the stub uses the bare name. */
    protected function suffix(): string
    {
        return '';
    }

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');
        $name = $this->cleanName($this->stringArgument('name'), 'name');
        $name = $this->stripSuffix($name);

        $namespace = $this->buildNamespace($context);
        $path = $this->contextPath($context, $this->subPath());
        File::ensureDirectoryExists($path);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub($this->stubName())
        );

        $file = "$path/{$name}{$this->suffix()}.php";

        if (! $this->writeFile($file, $content)) {
            return self::FAILURE;
        }

        $this->info("{$this->label()} created: $file");

        return self::SUCCESS;
    }

    /**
     * `clean:domain-event Billing InvoicePaidEvent` should produce
     * InvoicePaidEvent, not InvoicePaidEventEvent — the stub adds the suffix.
     */
    protected function stripSuffix(string $name): string
    {
        $suffix = $this->suffix();

        if ($suffix !== '' && $name !== $suffix && str_ends_with($name, $suffix)) {
            return substr($name, 0, -strlen($suffix));
        }

        return $name;
    }
}
