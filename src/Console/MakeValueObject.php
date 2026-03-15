<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeValueObject extends BaseGenerator
{
    protected $signature = 'clean:value-object {context} {name} {--force}';
    protected $description = 'Create a domain value object';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Domain/ValueObjects");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('value-object')
        );

        $file = "$path/$name.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Value object created: $file");
        }
    }
}
