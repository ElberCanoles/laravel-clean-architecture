<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeEntity extends SingleFileGenerator
{
    protected $signature = 'clean:entity {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a domain entity';

    protected function subPath(): string
    {
        return 'Domain/Entities';
    }

    protected function stubName(): string
    {
        return 'entity';
    }

    protected function label(): string
    {
        return 'Entity';
    }

    public function handle(): int
    {
        $result = parent::handle();

        if ($result === self::SUCCESS) {
            $this->ensureCreatedEvent(
                $this->cleanName($this->stringArgument('context'), 'context'),
                $this->cleanName($this->stringArgument('name'), 'name'),
            );
        }

        return $result;
    }

    /**
     * The entity's create() factory records {Entity}CreatedEvent — make sure
     * the event class exists so generated code never references a missing one.
     */
    protected function ensureCreatedEvent(string $context, string $name): void
    {
        $path = $this->contextPath($context, 'Domain/Events');
        $file = "$path/{$name}CreatedEvent.php";

        if (File::exists($file)) {
            return;
        }

        File::ensureDirectoryExists($path);

        $content = str_replace(
            ['{{Namespace}}', '{{Class}}'],
            [$this->buildNamespace($context), "{$name}Created"],
            $this->getStub('domain-event')
        );

        if (File::put($file, $content) === false) {
            $this->components->error("Could not write file: $file");

            return;
        }

        $this->info("Domain event created: $file");
    }
}
