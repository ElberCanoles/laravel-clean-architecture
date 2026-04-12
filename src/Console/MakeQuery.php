<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeQuery extends BaseGenerator
{
    protected $signature = 'clean:query {context} {name} {--entity= : Entity name to inject ReadRepository} {--collection : Generate a list/collection query} {--force}';
    protected $description = 'Create a CQRS query with handler and read model';

    public function handle(): int
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

        $base = base_path(config('clean-architecture.contexts_path') . "/$context/Application/Queries/$name");
        File::makeDirectory($base, 0755, true, true);

        $isCollection = $this->option('collection');

        $queryContent = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$namespace, $name],
            $this->getStub($isCollection ? 'list-query' : 'query')
        );

        $handlerStub = $this->getStub($isCollection ? 'list-query-handler' : 'query-handler');

        if ($entity) {
            $entityConstructor = "private readonly {$entity}ReadRepository \$repository,";
            $handlerBody = $isCollection
                ? 'return $this->repository->findAll($query->page, $query->perPage);'
                : 'return $this->repository->findById($query->id);';

            if ($isCollection) {
                $entityImport = "use {$namespace}\\Application\\Contracts\\{$entity}ReadRepository;\nuse CleanArchitecture\\Support\\PaginatedResult;";
                $returnType = 'PaginatedResult';
            } else {
                $entityImport = "use {$namespace}\\Application\\Contracts\\{$entity}ReadRepository;\nuse {$namespace}\\Application\\ReadModels\\{$entity}ReadModel;";
                $returnType = "?{$entity}ReadModel";
            }
        } else {
            $entityImport = '';
            $entityConstructor = '// TODO: Inject your ReadRepository';
            $returnType = $isCollection ? 'array' : 'mixed';
            $handlerBody = $isCollection
                ? "// TODO: Use repository to fetch and return read models\n        return [];"
                : "// TODO: Use repository to fetch and return read model\n        return null;";
        }

        $handlerContent = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{EntityImport}}', '{{EntityConstructor}}', '{{ReturnType}}', '{{HandlerBody}}'],
            [$namespace, $name, $entityImport, $entityConstructor, $returnType, $handlerBody],
            $handlerStub
        );

        $created = false;

        if ($this->writeFile("$base/{$name}Query.php", $queryContent)) {
            $created = true;
        }

        if ($this->writeFile("$base/{$name}Handler.php", $handlerContent)) {
            $created = true;
        }

        if ($created) {
            $this->info("Query created: $base");
        }

        return self::SUCCESS;
    }
}
