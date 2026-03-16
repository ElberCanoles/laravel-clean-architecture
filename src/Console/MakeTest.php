<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeTest extends BaseGenerator
{
    protected $signature = 'clean:test {context} {name} {--force}';
    protected $description = 'Create a Pest unit test for a domain object';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.unit_tests_path', 'tests/Unit/Domain') . "/$context");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('unit-test')
        );

        $file = "$path/{$name}Test.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Unit test created: $file");
        }
    }
}
