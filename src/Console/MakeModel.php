<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModel extends BaseGenerator
{
    protected $signature = 'clean:model {context} {name} {--force}';
    protected $description = 'Create an Eloquent model in the Infrastructure layer';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        $namespace = $this->buildNamespace($context);
        $table = Str::snake(Str::pluralStudly($name));

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Infrastructure/Models");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{table}}'],
            [$namespace, $name, $table],
            $this->getStub('model')
        );

        $file = "$path/{$name}Model.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Model created: $file");
        }
    }
}
