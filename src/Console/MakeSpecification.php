<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeSpecification extends BaseGenerator
{
    protected $signature = 'clean:specification {context} {name} {--force}';
    protected $description = 'Create a domain specification';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Domain/Specifications");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('specification')
        );

        $file = "$path/{$name}Specification.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Specification created: $file");
        }
    }
}
