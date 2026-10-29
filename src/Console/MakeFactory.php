<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeFactory extends SingleFileGenerator
{
    protected $signature = 'clean:factory {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create a model factory in the Infrastructure layer and wire it into the Eloquent model';

    protected function subPath(): string
    {
        return 'Infrastructure/Database/Factories';
    }

    protected function stubName(): string
    {
        return 'factory';
    }

    protected function suffix(): string
    {
        return 'ModelFactory';
    }

    protected function label(): string
    {
        return 'Factory';
    }

    public function handle(): int
    {
        $result = parent::handle();

        if ($result === self::SUCCESS) {
            $context = $this->cleanName($this->stringArgument('context'), 'context');
            $name = $this->stripSuffix($this->cleanName($this->stringArgument('name'), 'name'));

            $this->wireModel($context, $name);
        }

        return $result;
    }

    /**
     * Factories live outside Laravel's conventional path, so the model cannot
     * discover them: give it `HasFactory` and point `#[UseFactory]` at the
     * generated class so `{Entity}Model::factory()` works out of the box.
     */
    protected function wireModel(string $context, string $name): void
    {
        $modelPath = $this->contextPath($context, "Infrastructure/Models/{$name}Model.php");

        if (! File::exists($modelPath)) {
            $this->warn("Model {$name}Model not found — add HasFactory and #[UseFactory({$name}ModelFactory::class)] to it manually.");

            return;
        }

        $content = File::get($modelPath);

        // Already wired (by this command or by hand) — leave the file alone.
        if (str_contains($content, 'UseFactory(')) {
            return;
        }

        $factoryClass = "{$name}ModelFactory";
        $imports = [
            'Illuminate\Database\Eloquent\Attributes\UseFactory',
            $this->buildNamespace($context) . "\\Infrastructure\\Database\\Factories\\{$factoryClass}",
        ];

        $hasTrait = (bool) preg_match('/^\s*use\s+HasFactory\s*;/m', $content);

        if (! $hasTrait) {
            $imports[] = 'Illuminate\Database\Eloquent\Factories\HasFactory';
        }

        $updated = $this->addImports($content, $imports);
        $updated = $updated === null ? null : $this->addAttribute($updated, $name, $factoryClass);
        $updated = $updated === null || $hasTrait ? $updated : $this->addTrait($updated, $name);

        if ($updated === null) {
            $this->warn("Could not wire {$factoryClass} into {$name}Model — add HasFactory and #[UseFactory({$factoryClass}::class)] manually.");

            return;
        }

        if (File::put($modelPath, $updated) === false) {
            $this->components->error("Could not write file: $modelPath");

            return;
        }

        $this->info("Model wired: $modelPath");
    }

    /**
     * Merge the imports into the file's `use` block, keeping it sorted; a file
     * without imports gets the block right after its namespace declaration.
     *
     * @param  list<string>  $imports
     */
    protected function addImports(string $content, array $imports): ?string
    {
        $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";
        $classOffset = $this->classDeclarationOffset($content);

        if ($classOffset === null) {
            return null;
        }

        $header = substr($content, 0, $classOffset);
        $matched = preg_match_all('/^use\s+([^;]+);[ \t]*\R?/m', $header, $matches, PREG_OFFSET_CAPTURE);

        if ($matched === false) {
            return null;
        }

        $existing = array_map(static fn (array $match): string => trim($match[0]), $matches[1]);
        $block = array_unique(array_merge($existing, $imports));
        sort($block, SORT_STRING | SORT_FLAG_CASE);
        $block = implode($eol, array_map(static fn (string $import): string => "use $import;", $block)) . $eol;

        if ($matched > 0) {
            $first = $matches[0][0];
            $last = $matches[0][$matched - 1];
            $start = (int) $first[1];
            $end = (int) $last[1] + strlen($last[0]);

            return substr($content, 0, $start) . $block . substr($content, $end);
        }

        // No imports yet: insert after the namespace declaration.
        if (preg_match('/^namespace\s+[^;]+;[ \t]*\R?/m', $header, $namespace, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $position = (int) $namespace[0][1] + strlen($namespace[0][0]);

        return substr($content, 0, $position) . $eol . $block . substr($content, $position);
    }

    protected function addAttribute(string $content, string $name, string $factoryClass): ?string
    {
        $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";

        $updated = preg_replace(
            '/^((?:(?:final|abstract|readonly)\s+)*class\s+' . preg_quote("{$name}Model", '/') . '\b)/m',
            "#[UseFactory({$factoryClass}::class)]{$eol}$1",
            $content,
            1,
            $count
        );

        return $updated === null || $count !== 1 ? null : $updated;
    }

    protected function addTrait(string $content, string $name): ?string
    {
        $eol = str_contains($content, "\r\n") ? "\r\n" : "\n";
        $classOffset = $this->classDeclarationOffset($content);

        if ($classOffset === null) {
            return null;
        }

        $brace = strpos($content, '{', $classOffset);

        if ($brace === false) {
            return null;
        }

        $bodyStart = $brace + 1;
        $lineBreak = strpos($content, "\n", $bodyStart);
        $bodyStart = $lineBreak === false ? $bodyStart : $lineBreak + 1;

        // Keep trait imports together when the class already starts with one;
        // otherwise separate the new line from whatever follows.
        $startsWithUse = preg_match('/^\s*use\s+/', substr($content, $bodyStart, 200)) === 1;
        $line = "    use HasFactory;{$eol}" . ($startsWithUse ? '' : $eol);

        return substr($content, 0, $bodyStart) . $line . substr($content, $bodyStart);
    }

    /**
     * Byte offset of the model's class declaration, or null when it cannot be found.
     */
    protected function classDeclarationOffset(string $content): ?int
    {
        if (preg_match('/^(?:(?:final|abstract|readonly)\s+)*class\s+\w+Model\b/m', $content, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        return (int) $match[0][1];
    }
}
