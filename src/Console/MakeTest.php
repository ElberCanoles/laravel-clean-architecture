<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeTest extends BaseGenerator
{
    protected $signature = 'clean:test {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a Pest unit test for a domain object';

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');
        $name = $this->cleanName($this->stringArgument('name'), 'name');

        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.unit_tests_path', 'tests/Unit/Domain') . "/$context");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('unit-test')
        );

        $file = "$path/{$name}Test.php";

        if (! $this->writeFile($file, $content)) {
            return self::FAILURE;
        }

        $this->info("Unit test created: $file");

        return self::SUCCESS;
    }
}
