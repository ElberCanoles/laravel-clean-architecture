<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeController extends BaseGenerator
{
    protected $signature = 'clean:controller {context} {name} {--entity= : Entity name to wire CQRS handlers} {--id-type= : Identifier type generated in store() (uuid, ulid)} {--force : Overwrite existing files}';

    protected $description = 'Create a controller in the Presentation layer';

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');
        $name = $this->cleanName($this->stringArgument('name'), 'name');
        $entity = $this->stringOption('entity');

        if ($entity) {
            $entity = $this->cleanName($entity, 'entity');
        }

        $namespace = $this->buildNamespace($context);

        $path = $this->contextPath($context, 'Presentation/Controllers');
        File::makeDirectory($path, 0755, true, true);

        if ($entity) {
            $plural = $this->toPluralStudly($entity);

            // The id is generated at the presentation edge and travels in the
            // command, so Application handlers stay framework-free.
            $idFactory = $this->idFactoryCall($this->resolveIdType());

            $imports = "use Illuminate\\Support\\Str;\n"
                . "use {$namespace}\\Application\\Commands\\Create{$entity}\\Create{$entity}Command;\n"
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
                . "            page: max((int) \$request->query('page', 1), 1),\n"
                . "            perPage: min(max((int) \$request->query('per_page', 15), 1), 100),\n"
                . "        ));\n\n"
                . "        return {$entity}Resource::collection(\$result->items)\n"
                . "            ->additional(['meta' => \$result->meta()])\n"
                . '            ->response();';
            $showBody = "\$readModel = \$this->getHandler->handle(new Get{$entity}Query(\$id));\n        abort_if(! \$readModel, 404);\n\n        return (new {$entity}Resource(\$readModel))->response();";
            $storeBody = "\$id = (string) {$idFactory};\n"
                . "        \$sanitized = {$entity}Sanitizer::sanitize(\$request->validated());\n"
                . "        \$this->createHandler->handle(new Create{$entity}Command(\$id, \$sanitized));\n\n"
                . "        return response()->json(['id' => \$id], 201)\n"
                . "            ->header('Location', \$request->url() . '/' . \$id);";
            $updateBody = "\$sanitized = {$entity}Sanitizer::sanitize(\$request->validated());\n        \$this->updateHandler->handle(new Update{$entity}Command(\$id, \$sanitized));\n\n        return response()->noContent();";
            $destroyBody = "\$this->deleteHandler->handle(new Delete{$entity}Command(\$id));\n\n        return response()->noContent();";
        } else {
            $imports = '';
            $constructor = '// TODO: Inject command/query handlers';
            $indexBody = "// TODO: Implement list query using \$request->query('page') and \$request->query('per_page')\n        return response()->json([]);";
            $showBody = "// TODO: Implement show query\n        return response()->json([]);";
            $storeBody = "// TODO: Implement create command\n        return response()->json([], 201);";
            $updateBody = "// TODO: Implement update command\n        return response()->noContent();";
            $destroyBody = "// TODO: Implement delete command\n        return response()->noContent();";
        }

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{ControllerImports}}', '{{ControllerConstructor}}', '{{IndexBody}}', '{{ShowBody}}', '{{StoreBody}}', '{{UpdateBody}}', '{{DestroyBody}}'],
            [$namespace, $name, $imports, $constructor, $indexBody, $showBody, $storeBody, $updateBody, $destroyBody],
            $this->getStub('controller')
        );

        $file = "$path/{$name}Controller.php";

        if (! $this->writeFile($file, $content)) {
            return self::FAILURE;
        }

        $this->info("Controller created: $file");

        return self::SUCCESS;
    }
}
