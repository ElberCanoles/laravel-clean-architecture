<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeDomainEvent extends BaseGenerator
{
    protected $signature = 'clean:domain-event {context} {name} {--force}';
    protected $description = 'Create a domain event';

    public function handle(): int
    {
        $context = $this->argument('context');
        $name = $this->argument('name');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        $namespace = $this->buildNamespace($context);

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Domain/Events");
        File::makeDirectory($path, 0755, true, true);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('domain-event')
        );

        $file = "$path/{$name}Event.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Domain event created: $file");
        }

        return self::SUCCESS;
    }
}
