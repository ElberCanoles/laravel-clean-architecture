<?php

declare(strict_types=1);

namespace CleanArchitecture\Console\Concerns;

/**
 * The deterministic list of files clean:scaffold generates for an entity —
 * shared by --dry-run and clean:destroy so they can never drift apart.
 */
trait ScaffoldManifest
{
    /**
     * @return array<string, string> Label => absolute path
     */
    protected function scaffoldFiles(string $context, string $name): array
    {
        $plural = $this->hasOption('plural') && is_string($this->option('plural')) && $this->option('plural') !== ''
            ? $this->option('plural')
            : $this->toPluralStudly($name);
        $base = fn (string $subPath): string => $this->contextPath($context, $subPath);

        return [
            'Entity' => $base("Domain/Entities/{$name}.php"),
            'Creation event' => $base("Domain/Events/{$name}CreatedEvent.php"),
            'NotFound exception' => $base("Domain/Exceptions/{$name}NotFound.php"),
            'Write repository interface' => $base("Domain/Repositories/{$name}WriteRepository.php"),
            'Read repository contract' => $base("Application/Contracts/{$name}ReadRepository.php"),
            'Read model' => $base("Application/ReadModels/{$name}ReadModel.php"),
            'Sanitizer' => $base("Application/Sanitizers/{$name}Sanitizer.php"),
            'Create command' => $base("Application/Commands/Create{$name}/Create{$name}Command.php"),
            'Create handler' => $base("Application/Commands/Create{$name}/Create{$name}Handler.php"),
            'Update command' => $base("Application/Commands/Update{$name}/Update{$name}Command.php"),
            'Update handler' => $base("Application/Commands/Update{$name}/Update{$name}Handler.php"),
            'Delete command' => $base("Application/Commands/Delete{$name}/Delete{$name}Command.php"),
            'Delete handler' => $base("Application/Commands/Delete{$name}/Delete{$name}Handler.php"),
            'Get query' => $base("Application/Queries/Get{$name}/Get{$name}Query.php"),
            'Get handler' => $base("Application/Queries/Get{$name}/Get{$name}Handler.php"),
            'List query' => $base("Application/Queries/List{$plural}/List{$plural}Query.php"),
            'List handler' => $base("Application/Queries/List{$plural}/List{$plural}Handler.php"),
            'Eloquent model' => $base("Infrastructure/Models/{$name}Model.php"),
            'Write Eloquent repository' => $base("Infrastructure/{$name}WriteEloquentRepository.php"),
            'Read Eloquent repository' => $base("Infrastructure/{$name}ReadEloquentRepository.php"),
            'In-memory write repository' => $base("Infrastructure/InMemory{$name}WriteRepository.php"),
            'In-memory read repository' => $base("Infrastructure/InMemory{$name}ReadRepository.php"),
            'Mapper' => $base("Infrastructure/{$name}Mapper.php"),
            'Controller' => $base("Presentation/Controllers/{$name}Controller.php"),
            'Store request' => $base("Presentation/Requests/Store{$name}Request.php"),
            'Update request' => $base("Presentation/Requests/Update{$name}Request.php"),
            'Resource' => $base("Presentation/Resources/{$name}Resource.php"),
        ];
    }
}
