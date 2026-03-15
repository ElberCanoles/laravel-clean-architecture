<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeCommand extends BaseGenerator
{
    protected $signature = 'clean:command {context} {name} {--force}';
    protected $description = 'Create a CQRS command with handler';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $namespace = $this->buildNamespace($context);

        $base = base_path(config('clean-architecture.contexts_path') . "/$context/Application/Commands/$name");
        File::makeDirectory($base, 0755, true, true);

        $commandContent = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('command')
        );

        $handlerContent = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('command-handler')
        );

        $created = false;

        if ($this->writeFile("$base/{$name}Command.php", $commandContent)) {
            $created = true;
        }

        if ($this->writeFile("$base/{$name}Handler.php", $handlerContent)) {
            $created = true;
        }

        if ($created) {
            $this->info("Command created: $base");
        }
    }
}
