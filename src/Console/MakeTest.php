<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeTest extends BaseGenerator
{
    protected $signature = 'clean:test {context} {name} {--handler : Generate a use-case test against the in-memory repositories} {--feature : Generate an HTTP feature test for the resource endpoints} {--force : Overwrite existing files}';

    protected $description = 'Create a Pest test: entity unit test (default), --handler, or --feature';

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');
        $name = $this->cleanName($this->stringArgument('name'), 'name');

        if ($this->option('handler') && $this->option('feature')) {
            throw new \InvalidArgumentException('Use either --handler or --feature, not both.');
        }

        [$stub, $basePath, $fileName, $label] = match (true) {
            (bool) $this->option('handler') => [
                'handler-test',
                (string) config('clean-architecture.handler_tests_path', 'tests/Unit/Application'),
                "{$name}HandlersTest.php",
                'Handler test',
            ],
            (bool) $this->option('feature') => [
                'feature-test',
                (string) config('clean-architecture.feature_tests_path', 'tests/Feature'),
                "{$name}ApiTest.php",
                'Feature test',
            ],
            default => [
                'unit-test',
                (string) config('clean-architecture.unit_tests_path', 'tests/Unit/Domain'),
                "{$name}Test.php",
                'Unit test',
            ],
        };

        $path = base_path("$basePath/$context");
        File::ensureDirectoryExists($path);

        $routeBase = $this->toKebab($context) . '/' . $this->toKebabPlural($name);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{routeBase}}'],
            [$this->buildNamespace($context), $name, $routeBase],
            $this->getStub($stub)
        );

        $file = "$path/$fileName";

        if (! $this->writeFile($file, $content)) {
            return self::FAILURE;
        }

        $this->info("$label created: $file");

        return self::SUCCESS;
    }
}
