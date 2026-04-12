<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeException extends BaseGenerator
{
    protected $signature = 'clean:exception {context} {name} {--force}';
    protected $description = 'Create a domain exception';

    public function handle(): int
    {
        $context = $this->argument('context');
        $name = $this->argument('name');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Domain/Exceptions");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('domain-exception')
        );

        $file = "$path/{$name}Exception.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Domain exception created: $file");
        }

        return self::SUCCESS;
    }
}
