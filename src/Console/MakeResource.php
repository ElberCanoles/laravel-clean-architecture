<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeResource extends BaseGenerator
{
    protected $signature = 'clean:resource {context} {name} {--force}';
    protected $description = 'Create an API resource in the Presentation layer';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Presentation/Resources");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('resource')
        );

        $file = "$path/{$name}Resource.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Resource created: $file");
        }
    }
}
