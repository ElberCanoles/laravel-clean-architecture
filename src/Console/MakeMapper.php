<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeMapper extends BaseGenerator
{
    protected $signature = 'clean:mapper {context} {name} {--force}';
    protected $description = 'Create an entity-model mapper in the Infrastructure layer';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Infrastructure");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('mapper')
        );

        $file = "$path/{$name}Mapper.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Mapper created: $file");
        }
    }
}
