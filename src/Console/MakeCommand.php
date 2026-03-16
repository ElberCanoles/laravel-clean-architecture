<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeCommand extends BaseGenerator
{
    protected $signature = 'clean:command {context} {name} {--entity= : Entity name to inject WriteRepository} {--force}';
    protected $description = 'Create a CQRS command with handler';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $entity = $this->option('entity');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        if ($entity) {
            $this->validateName($entity, 'entity');
        }

        $namespace = $this->buildNamespace($context);

        $base = base_path(config('clean-architecture.contexts_path') . "/$context/Application/Commands/$name");
        File::makeDirectory($base, 0755, true, true);

        $commandContent = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('command')
        );

        $handlerStub = $this->getStub('command-handler');

        if ($entity) {
            $entityImport = "use {$namespace}\\Domain\\Repositories\\{$entity}WriteRepository;";
            $entityConstructor = "private readonly {$entity}WriteRepository \$repository,";
        } else {
            $entityImport = '';
            $entityConstructor = '// TODO: Inject your WriteRepository';
        }

        $handlerContent = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{EntityImport}}', '{{EntityConstructor}}'],
            [$namespace, $name, $entityImport, $entityConstructor],
            $handlerStub
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
