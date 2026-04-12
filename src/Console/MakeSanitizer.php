<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeSanitizer extends BaseGenerator
{
    protected $signature = 'clean:sanitizer {context} {name} {--force}';
    protected $description = 'Create a sanitizer in the Application layer';

    public function handle(): int
    {
        $context = $this->argument('context');
        $name = $this->argument('name');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Application/Sanitizers");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('sanitizer')
        );

        $file = "$path/{$name}Sanitizer.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Sanitizer created: $file");
        }

        return self::SUCCESS;
    }
}
