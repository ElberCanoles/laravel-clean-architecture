<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeEntity extends BaseGenerator
{
    protected $signature = 'clean:entity {context} {name} {--force}';
    protected $description = 'Create a domain entity';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Domain/Entities");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('entity')
        );

        $file = "$path/$name.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Entity created: $file");
        }
    }
}
