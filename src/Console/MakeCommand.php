<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeCommand extends BaseGenerator
{
    protected $signature = 'clean:command {context} {name} {--entity= : Entity name to inject WriteRepository} {--crud= : CRUD operation type (create, update, delete)} {--force}';
    protected $description = 'Create a CQRS command with handler';

    public function handle(): int
    {
        $context = $this->argument('context');
        $name = $this->argument('name');
        $entity = $this->option('entity');
        $crud = $this->option('crud');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        if ($entity) {
            $this->validateName($entity, 'entity');
        }

        if ($crud && ! in_array($crud, ['create', 'update', 'delete'])) {
            throw new \InvalidArgumentException(
                "Invalid --crud value: '$crud'. Must be 'create', 'update', or 'delete'."
            );
        }

        $namespace = $this->buildNamespace($context);

        $base = base_path(config('clean-architecture.contexts_path') . "/$context/Application/Commands/$name");
        File::makeDirectory($base, 0755, true, true);

        $commandConstructor = $this->buildCommandConstructor($crud);

        $commandContent = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{CommandConstructor}}'],
            [$namespace, $name, $commandConstructor],
            $this->getStub('command')
        );

        $handlerStub = $this->getStub('command-handler');

        if ($entity) {
            $entityImport = $this->buildEntityImport($namespace, $entity, $crud);
            $entityConstructor = "private readonly {$entity}WriteRepository \$repository,";
            $handlerBody = $this->buildHandlerBody($crud, $entity);
        } else {
            $entityImport = '';
            $entityConstructor = '// TODO: Inject your WriteRepository';
            $handlerBody = '// TODO: Load or create entity, execute domain logic, persist via repository';
        }

        $handlerContent = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{EntityImport}}', '{{EntityConstructor}}', '{{HandlerBody}}'],
            [$namespace, $name, $entityImport, $entityConstructor, $handlerBody],
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

        return self::SUCCESS;
    }

    protected function buildCommandConstructor(?string $crud): string
    {
        return match ($crud) {
            'create' => 'public array $data,',
            'update' => "public string \$id,\n        public array \$data,",
            'delete' => 'public string $id,',
            default => 'public string $id,',
        };
    }

    protected function buildEntityImport(string $namespace, string $entity, ?string $crud): string
    {
        $imports = "use {$namespace}\\Domain\\Repositories\\{$entity}WriteRepository;";

        if ($crud === 'create') {
            $imports = "use {$namespace}\\Domain\\Entities\\{$entity};\n{$imports}\nuse Illuminate\\Support\\Str;";
        } elseif ($crud === 'update') {
            $imports = "use {$namespace}\\Domain\\Entities\\{$entity};\n{$imports}";
        }

        return $imports;
    }

    protected function buildHandlerBody(?string $crud, string $entity): string
    {
        return match ($crud) {
            'create' => "\$entity = {$entity}::create((string) Str::uuid7());\n        \$this->repository->save(\$entity);",
            'update' => '// TODO: Load entity, apply changes from $command->data, persist via repository',
            'delete' => '$this->repository->delete($command->id);',
            default => '// TODO: Load or create entity, execute domain logic, persist via repository',
        };
    }
}
