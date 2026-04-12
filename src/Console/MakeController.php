<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeController extends BaseGenerator
{
    protected $signature = 'clean:controller {context} {name} {--entity= : Entity name to wire CQRS handlers} {--force}';
    protected $description = 'Create a controller in the Presentation layer';

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

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Presentation/Controllers");
        File::makeDirectory($path, 0755, true, true);

        if ($entity) {
            $plural = $this->toPluralStudly($entity);

            $imports = "use {$namespace}\\Application\\Commands\\Create{$entity}\\Create{$entity}Command;\n"
                . "use {$namespace}\\Application\\Commands\\Create{$entity}\\Create{$entity}Handler;\n"
                . "use {$namespace}\\Application\\Commands\\Update{$entity}\\Update{$entity}Command;\n"
                . "use {$namespace}\\Application\\Commands\\Update{$entity}\\Update{$entity}Handler;\n"
                . "use {$namespace}\\Application\\Commands\\Delete{$entity}\\Delete{$entity}Command;\n"
                . "use {$namespace}\\Application\\Commands\\Delete{$entity}\\Delete{$entity}Handler;\n"
                . "use {$namespace}\\Application\\Queries\\Get{$entity}\\Get{$entity}Handler;\n"
                . "use {$namespace}\\Application\\Queries\\Get{$entity}\\Get{$entity}Query;\n"
                . "use {$namespace}\\Application\\Queries\\List{$plural}\\List{$plural}Handler;\n"
                . "use {$namespace}\\Application\\Queries\\List{$plural}\\List{$plural}Query;\n"
                . "use {$namespace}\\Application\\Sanitizers\\{$entity}Sanitizer;\n";

            $constructor = "private readonly Create{$entity}Handler \$createHandler,\n"
                . "        private readonly Update{$entity}Handler \$updateHandler,\n"
                . "        private readonly Delete{$entity}Handler \$deleteHandler,\n"
                . "        private readonly Get{$entity}Handler \$getHandler,\n"
                . "        private readonly List{$plural}Handler \$listHandler,";

            $indexBody = "\$result = \$this->listHandler->handle(new List{$plural}Query(\n"
                . "            page: (int) \$request->query('page', 1),\n"
                . "            perPage: (int) \$request->query('per_page', 15),\n"
                . "        ));\n\n"
                . "        return {$entity}Resource::collection(\$result->items)\n"
                . "            ->additional(['meta' => \$result->meta()])\n"
                . "            ->response();";
            $showBody = "\$readModel = \$this->getHandler->handle(new Get{$entity}Query(\$id));\n        abort_if(! \$readModel, 404);\n\n        return (new {$entity}Resource(\$readModel))->response();";
            $storeBody = "\$sanitized = {$entity}Sanitizer::sanitize(\$request->validated());\n        \$this->createHandler->handle(new Create{$entity}Command(\$sanitized));\n\n        return response()->json([], 201);";
            $updateBody = "\$sanitized = {$entity}Sanitizer::sanitize(\$request->validated());\n        \$this->updateHandler->handle(new Update{$entity}Command(\$id, \$sanitized));\n\n        return response()->json([]);";
            $destroyBody = "\$this->deleteHandler->handle(new Delete{$entity}Command(\$id));\n\n        return response()->json([], 204);";
        } else {
            $imports = '';
            $constructor = '// TODO: Inject command/query handlers';
            $indexBody = "// TODO: Implement list query using \$request->query('page') and \$request->query('per_page')\n        return response()->json([]);";
            $showBody = "// TODO: Implement show query\n        return response()->json([]);";
            $storeBody = "// TODO: Implement create command\n        return response()->json([], 201);";
            $updateBody = "// TODO: Implement update command\n        return response()->json([]);";
            $destroyBody = "// TODO: Implement delete command\n        return response()->json([], 204);";
        }

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{ControllerImports}}', '{{ControllerConstructor}}', '{{IndexBody}}', '{{ShowBody}}', '{{StoreBody}}', '{{UpdateBody}}', '{{DestroyBody}}'],
            [$namespace, $name, $imports, $constructor, $indexBody, $showBody, $storeBody, $updateBody, $destroyBody],
            $this->getStub('controller')
        );

        $file = "$path/{$name}Controller.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Controller created: $file");
        }

        return self::SUCCESS;
    }
}
