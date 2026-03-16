<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeController extends BaseGenerator
{
    protected $signature = 'clean:controller {context} {name} {--force}';
    protected $description = 'Create a controller in the Presentation layer';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Presentation/Controllers");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('controller')
        );

        $file = "$path/{$name}Controller.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Controller created: $file");
        }
    }
}
