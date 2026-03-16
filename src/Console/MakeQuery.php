<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeQuery extends BaseGenerator
{
    protected $signature = 'clean:query {context} {name} {--entity= : Entity name to inject ReadRepository} {--force}';
    protected $description = 'Create a CQRS query with handler and read model';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $namespace = $this->buildNamespace($context);
        $entity = $this->option('entity');

        $base = base_path(config('clean-architecture.contexts_path') . "/$context/Application/Queries/$name");
        File::makeDirectory($base, 0755, true, true);

        $queryContent = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('query')
        );

        $handlerStub = $this->getStub('query-handler');

        if ($entity) {
            $entityImport = "use {$namespace}\\Application\\Contracts\\{$entity}ReadRepository;";
            $entityConstructor = "private readonly {$entity}ReadRepository \$repository,";
        } else {
            $entityImport = '';
            $entityConstructor = '// TODO: Inject your ReadRepository';
        }

        $handlerContent = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{EntityImport}}', '{{EntityConstructor}}'],
            [$namespace, $name, $entityImport, $entityConstructor],
            $handlerStub
        );

        $readModelContent = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('query-read-model')
        );

        $created = false;

        if ($this->writeFile("$base/{$name}Query.php", $queryContent)) {
            $created = true;
        }

        if ($this->writeFile("$base/{$name}Handler.php", $handlerContent)) {
            $created = true;
        }

        if ($this->writeFile("$base/{$name}ReadModel.php", $readModelContent)) {
            $created = true;
        }

        if ($created) {
            $this->info("Query created: $base");
        }
    }
}
