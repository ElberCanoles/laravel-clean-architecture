<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeReadModel extends BaseGenerator
{
    protected $signature = 'clean:read-model {context} {name} {--force}';
    protected $description = 'Create an application read model';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Application/ReadModels");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('read-model')
        );

        $file = "$path/{$name}ReadModel.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Read model created: $file");
        }
    }
}
