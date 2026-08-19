<?php

declare(strict_types=1);

namespace CleanArchitecture\Console;

use Illuminate\Support\Facades\File;

class MakeRequest extends BaseGenerator
{
    protected $signature = 'clean:request {context} {name} {--force : Overwrite existing files}';

    protected $description = 'Create Store and Update form requests in the Presentation layer';

    public function handle(): int
    {
        $context = $this->cleanName($this->stringArgument('context'), 'context');
        $name = $this->cleanName($this->stringArgument('name'), 'name');

        $namespace = $this->buildNamespace($context);
        $path = $this->contextPath($context, 'Presentation/Requests');
        File::ensureDirectoryExists($path);

        $wroteStore = $this->writeFile(
            "$path/Store{$name}Request.php",
            str_replace(['{{Namespace}}', '{{Class}}'], [$namespace, $name], $this->getStub('store-request'))
        );

        $wroteUpdate = $this->writeFile(
            "$path/Update{$name}Request.php",
            str_replace(['{{Namespace}}', '{{Class}}'], [$namespace, $name], $this->getStub('update-request'))
        );

        if (! $wroteStore && ! $wroteUpdate) {
            return self::FAILURE;
        }

        $this->info("Requests created: $path");

        return self::SUCCESS;
    }
}
