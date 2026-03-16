<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeController extends BaseGenerator
{
    protected $signature = 'clean:controller {context} {name} {--entity= : Entity name to wire CQRS handlers} {--force}';
    protected $description = 'Create a controller in the Presentation layer';

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

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Presentation/Controllers");
        File::makeDirectory($path, 0755, true, true);

        if ($entity) {
            $imports = "use {$namespace}\\Application\\Commands\\Create{$entity}\\Create{$entity}Command;\n"
                . "use {$namespace}\\Application\\Commands\\Create{$entity}\\Create{$entity}Handler;\n"
                . "use {$namespace}\\Application\\Queries\\Get{$entity}\\Get{$entity}Handler;\n"
                . "use {$namespace}\\Application\\Queries\\Get{$entity}\\Get{$entity}Query;\n"
                . "use {$namespace}\\Application\\Sanitizers\\{$entity}Sanitizer;\n";
            $constructor = "private readonly Create{$entity}Handler \$createHandler,\n        private readonly Get{$entity}Handler \$getHandler,";
            $showBody = "\$readModel = \$this->getHandler->handle(new Get{$entity}Query(\$id));\n\n        return (new {$entity}Resource(\$readModel))->response();";
            $storeBody = "\$sanitized = {$entity}Sanitizer::sanitize(\$request->validated());\n        \$this->createHandler->handle(new Create{$entity}Command(...\$sanitized));\n\n        return response()->json([], 201);";
        } else {
            $imports = '';
            $constructor = '// TODO: Inject command/query handlers';
            $showBody = "// TODO: Implement show query\n        return response()->json([]);";
            $storeBody = "// TODO: Implement create command\n        return response()->json([], 201);";
        }

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{ControllerImports}}', '{{ControllerConstructor}}', '{{ShowBody}}', '{{StoreBody}}'],
            [$namespace, $name, $imports, $constructor, $showBody, $storeBody],
            $this->getStub('controller')
        );

        $file = "$path/{$name}Controller.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Controller created: $file");
        }
    }
}
