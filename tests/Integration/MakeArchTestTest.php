<?php

use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Run one rule of a generated architecture test in a real Pest process, the
 * way a user's CI would: substring checks cannot tell a rule that works from
 * one that can never fail.
 *
 * The context is copied into a throwaway project first, because the Arch
 * plugin reads no dependencies from files under a vendor/ directory: inside
 * the Testbench skeleton a dependency rule passes whatever the code does.
 * The project lives in .phpunit.cache — git-ignored, outside vendor/ and
 * tests/, and a path Pest can turn into a test class name (a system temp
 * directory with a segment that starts with a digit is not).
 *
 * $autoloaded says whether Laravel booted before the run — that is what
 * autoloads a context — or the test runs in a process where it never did.
 *
 * @return array{0: int|null, 1: string} Exit code and output
 */
function runGeneratedArchRule(string $tempDir, string $context, string $rule, bool $autoloaded = true): array
{
    $root = dirname(__DIR__, 2);
    $project = $root . '/.phpunit.cache/' . basename($tempDir);

    try {
        File::copyDirectory("$tempDir/$context", "$project/src/$context");
        File::copyDirectory("$tempDir/tests/Feature/Architecture", "$project/tests");

        File::put("$project/phpunit.xml", <<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <phpunit bootstrap="bootstrap.php">
                <testsuites>
                    <testsuite name="Architecture">
                        <directory>tests</directory>
                    </testsuite>
                </testsuites>
            </phpunit>
            XML);

        $bootstrap = sprintf('<?php $loader = require %s;', var_export("$root/vendor/autoload.php", true));

        if ($autoloaded) {
            // What ModuleLoader does for the context when the application boots.
            $bootstrap .= sprintf(
                ' $loader->addPsr4(%s, %s);',
                var_export("Src\\$context\\", true),
                var_export("$project/src/$context", true),
            );
        }

        File::put("$project/bootstrap.php", $bootstrap);

        $process = new Process(
            [
                PHP_BINARY, "$root/vendor/pestphp/pest/bin/pest",
                '--configuration', "$project/phpunit.xml", '--filter', $rule, '--colors=never',
            ],
            $root,
            // A run of its own, not a worker of the suite that launched it.
            array_fill_keys(['PARATEST', 'TEST_TOKEN', 'UNIQUE_TEST_TOKEN'], false),
        );

        $process->run();

        return [$process->getExitCode(), $process->getOutput() . $process->getErrorOutput()];
    } finally {
        File::deleteDirectory($project);
    }
}

test('creates architecture test file with correct content', function () {
    $this->artisan('clean:arch-test', ['context' => 'Billing'])
        ->expectsOutputToContain('Architecture test created')
        ->assertSuccessful();

    $file = $this->tempDir . '/tests/Feature/Architecture/BillingArchTest.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('Billing classes are visible to the architecture tests')
        ->toContain("expect('Src\Billing')->toBeInterfaces()")
        ->toContain("expect('Src\Billing\Domain')")
        ->toContain('Billing domain does not depend on infrastructure')
        ->toContain("->toOnlyUse('CleanArchitecture\Support')")
        ->toContain('Billing domain does not depend on application layer')
        ->toContain('Billing application does not depend on presentation')
        ->toContain('Billing application does not depend on infrastructure')
        ->toContain('Billing application does not depend on the framework')
        ->toContain('Billing presentation does not depend on infrastructure')
        ->toContain("->not->toUse('Src\Billing\Infrastructure')")
        ->toContain('Billing eloquent models never leave infrastructure')
        ->toContain("->toOnlyBeUsedIn('Src\Billing\Infrastructure')")
        ->toContain('Billing code declares strict types')
        ->toContain('->toUseStrictTypes()')
        ->toContain('Billing entities are final classes')
        ->toContain('Billing repositories in domain are interfaces')
        ->toContain('Billing value objects are readonly');
});

test('uses configured namespace prefix in arch tests', function () {
    config(['clean-architecture.namespace_prefix' => 'Domain']);

    $this->artisan('clean:arch-test', ['context' => 'Sales'])
        ->assertSuccessful();

    $file = $this->tempDir . '/tests/Feature/Architecture/SalesArchTest.php';
    $content = file_get_contents($file);

    expect($content)
        ->toContain("expect('Domain\Sales')->toBeInterfaces()")
        ->toContain("expect('Domain\Sales\Domain')");
});

test('warns when arch test file exists without --force', function () {
    $this->artisan('clean:arch-test', ['context' => 'Billing']);

    $this->artisan('clean:arch-test', ['context' => 'Billing'])
        ->expectsOutputToContain('File already exists')
        ->doesntExpectOutputToContain('Architecture test created')
        ->assertExitCode(1);
});

test('the generated domain rule accepts generated domain code and rejects a framework dependency', function () {
    // Everything the generators write into the Domain layer.
    $this->artisan('clean:context', ['name' => 'Billing'])->assertSuccessful();
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    foreach ([
        'clean:domain-event' => 'InvoicePaid',
        'clean:exception' => 'InvoiceLocked',
        'clean:specification' => 'InvoiceOverdue',
        'clean:value-object' => 'Money',
        'clean:enum' => 'InvoiceStatus',
        'clean:domain-service' => 'InvoicePricer',
    ] as $command => $name) {
        $this->artisan($command, ['context' => 'Billing', 'name' => $name])->assertSuccessful();
    }

    [$exitCode, $output] = runGeneratedArchRule($this->tempDir, 'Billing', 'domain does not depend on infrastructure');

    expect($exitCode)->toBe(0, $output);

    File::put($this->tempDir . '/Billing/Domain/Services/InvoiceCounter.php', <<<'PHP'
        <?php

        declare(strict_types=1);

        namespace Src\Billing\Domain\Services;

        use Illuminate\Support\Facades\DB;

        final class InvoiceCounter
        {
            public function count(): int
            {
                return DB::table('invoices')->count();
            }
        }
        PHP);

    [$exitCode, $output] = runGeneratedArchRule($this->tempDir, 'Billing', 'domain does not depend on infrastructure');

    // The same run plus one class: exit code 1 means the rule itself failed.
    expect($exitCode)->toBe(1, $output);
})->skip(
    getenv('PEST_MUTATION_TESTING') !== false,
    'Spawns Pest itself: worth running once per suite, not once per mutant.',
);

test('the generated guard passes when Pest sees the context and fails when it does not', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])->assertSuccessful();

    [$exitCode, $output] = runGeneratedArchRule($this->tempDir, 'Billing', 'classes are visible to the architecture tests');

    expect($exitCode)->toBe(0, $output);

    [$exitCode, $output] = runGeneratedArchRule($this->tempDir, 'Billing', 'classes are visible to the architecture tests', autoloaded: false);

    // The same run minus the autoloading: every other rule in the file would
    // pass here without a single class to check, so this one has to fail.
    expect($exitCode)->toBe(1, $output);
})->skip(
    getenv('PEST_MUTATION_TESTING') !== false,
    'Spawns Pest itself: worth running once per suite, not once per mutant.',
);
