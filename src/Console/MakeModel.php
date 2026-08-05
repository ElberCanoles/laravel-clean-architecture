<?php

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeModel extends BaseGenerator
{
    protected $signature = 'clean:model {context} {name} {--id-type= : Primary key type (uuid, ulid)} {--force}';
    protected $description = 'Create an Eloquent model in the Infrastructure layer';

    public function handle(): int
    {
        $context = $this->argument('context');
        $name = $this->argument('name');

        $this->validateName($context, 'context');
        $this->validateName($name, 'name');

        $namespace = $this->buildNamespace($context);
        $table = Str::snake(Str::pluralStudly($name));
        $idTrait = $this->idTrait($this->resolveIdType());

        $path = base_path(config('clean-architecture.contexts_path') . "/$context/Infrastructure/Models");
        File::makeDirectory($path, 0755, true, true);

        $stub = $this->getStub('model');
        $this->warnIfStubIgnoresIdType($stub, 'model', '{{IdTrait}}');

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}', '{{table}}', '{{IdTrait}}'],
            [$namespace, $name, $table, $idTrait],
            $stub
        );

        $file = "$path/{$name}Model.php";

        if ($this->writeFile($file, $content)) {
            $this->info("Model created: $file");
        }

        return self::SUCCESS;
    }
}
