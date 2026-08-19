<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeArchTest extends BaseGenerator
{
    protected $signature = 'clean:arch-test {context} {--force : Overwrite existing files}';

    protected $description = 'Create architecture tests for a bounded context';

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');

        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.arch_tests_path', 'tests/Feature/Architecture'));
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Context}}'],
            [$namespace, $context],
            $this->getStub('arch-test')
        );

        $file = "$path/{$context}ArchTest.php";

        if (! $this->writeFile($file, $content)) {
            return self::FAILURE;
        }

        $this->info("Architecture test created: $file");

        return self::SUCCESS;
    }
}
