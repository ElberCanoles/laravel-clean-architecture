<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeRequest extends BaseGenerator
{
    protected $signature = 'clean:request {context} {name} {--force}';
    protected $description = 'Create a form request in the Presentation layer';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Presentation/Requests");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('request')
        );

        $file = "$path/{$name}Request.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Request created: $file");
        }
    }
}
