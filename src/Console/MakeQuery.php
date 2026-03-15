<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeQuery extends BaseGenerator
{
    protected $signature = 'clean:query {context} {name} {--force}';
    protected $description = 'Create a CQRS query with handler and read model';

    public function handle(): void
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $namespace = $this->buildNamespace($context);

        $base = base_path(config('clean-architecture.contexts_path') . "/$context/Application/Queries/$name");
        File::makeDirectory($base, 0755, true, true);

        $queryContent = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('query')
        );

        $handlerContent = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub('query-handler')
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
