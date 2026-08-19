<?php

declare(strict_types=1);

/*
 * Every single-file generator honours the same contract:
 *   1. creates the file and exits 0,
 *   2. refuses to touch an existing file without --force (warn + exit 1),
 *   3. actually replaces the content with --force,
 * and the file it writes is valid PHP.
 */
test('generator honours the create, exists, and force contract', function (string $command, string $name, string $relativePath) {
    $args = ['context' => 'Billing', 'name' => $name];
    $file = $this->tempDir . '/' . $relativePath;

    $this->artisan($command, $args)->assertSuccessful();
    expect(file_exists($file))->toBeTrue();
    expect($file)->toBeValidPhp();

    file_put_contents($file, '<?php // user edits');
    $this->artisan($command, $args)
        ->expectsOutputToContain('File already exists')
        ->assertExitCode(1);
    expect(file_get_contents($file))->toBe('<?php // user edits');

    $this->artisan($command, array_merge($args, ['--force' => true]))->assertSuccessful();
    expect(file_get_contents($file))->not->toContain('user edits');
    expect($file)->toBeValidPhp();
})->with([
    'entity' => ['clean:entity', 'Invoice', 'Billing/Domain/Entities/Invoice.php'],
    'value object' => ['clean:value-object', 'Money', 'Billing/Domain/ValueObjects/Money.php'],
    'read model' => ['clean:read-model', 'Invoice', 'Billing/Application/ReadModels/InvoiceReadModel.php'],
    'specification' => ['clean:specification', 'InvoiceOverdue', 'Billing/Domain/Specifications/InvoiceOverdueSpecification.php'],
    'domain event' => ['clean:domain-event', 'InvoicePaid', 'Billing/Domain/Events/InvoicePaidEvent.php'],
    'domain exception' => ['clean:exception', 'InvoiceLocked', 'Billing/Domain/Exceptions/InvoiceLockedException.php'],
    'mapper' => ['clean:mapper', 'Invoice', 'Billing/Infrastructure/InvoiceMapper.php'],
    'sanitizer' => ['clean:sanitizer', 'Invoice', 'Billing/Application/Sanitizers/InvoiceSanitizer.php'],
    'resource' => ['clean:resource', 'Invoice', 'Billing/Presentation/Resources/InvoiceResource.php'],
    'model' => ['clean:model', 'Invoice', 'Billing/Infrastructure/Models/InvoiceModel.php'],
    'controller' => ['clean:controller', 'Invoice', 'Billing/Presentation/Controllers/InvoiceController.php'],
    'unit test' => ['clean:test', 'Invoice', 'tests/Unit/Domain/Billing/InvoiceTest.php'],
    'enum' => ['clean:enum', 'InvoiceStatus', 'Billing/Domain/Enums/InvoiceStatus.php'],
    'policy' => ['clean:policy', 'Invoice', 'Billing/Presentation/Policies/InvoicePolicy.php'],
    'factory' => ['clean:factory', 'Invoice', 'Billing/Infrastructure/Database/Factories/InvoiceModelFactory.php'],
    'seeder' => ['clean:seeder', 'Invoice', 'Billing/Infrastructure/Database/Seeders/InvoiceSeeder.php'],
    'listener' => ['clean:listener', 'SendInvoiceMail', 'Billing/Application/Listeners/SendInvoiceMailListener.php'],
    'job' => ['clean:job', 'SyncInvoice', 'Billing/Infrastructure/Jobs/SyncInvoiceJob.php'],
    'domain service' => ['clean:domain-service', 'InvoicePricer', 'Billing/Domain/Services/InvoicePricer.php'],
]);

test('stubs and generators reference each other with no orphans', function () {
    // A stub renamed or dropped should fail here, not at the user's runtime.
    $stubsOnDisk = collect(glob(__DIR__ . '/../../stubs/*.stub'))
        ->map(fn (string $path) => basename($path, '.stub'))
        ->sort()
        ->values();

    $sources = collect(glob(__DIR__ . '/../../src/Console/*.php'))
        ->map(fn (string $path) => (string) file_get_contents($path));

    // Literal getStub('name') calls plus SingleFileGenerator stubName() returns
    // (lowercase kebab-case only, so labels and sub-paths never match), plus
    // the query family resolved through ternaries in MakeQuery.
    $referenced = $sources
        ->flatMap(function (string $source) {
            preg_match_all("/getStub\\('([a-z][a-z-]*)'\\)/", $source, $direct);
            preg_match_all("/return '([a-z][a-z-]*)';/", $source, $conventional);

            return array_merge($direct[1], $conventional[1]);
        })
        // Resolved through ternaries (MakeQuery) or match arms (MakeTest).
        ->merge(['query', 'list-query', 'query-handler', 'unit-test', 'handler-test', 'feature-test'])
        ->unique()
        ->sort()
        ->values();

    $orphanStubs = $stubsOnDisk->diff($referenced);
    $missingStubs = $referenced->diff($stubsOnDisk)->reject(fn (string $name) => $name === 'api');

    expect($orphanStubs->values()->all())->toBe([]);
    expect($missingStubs->values()->all())->toBe([]);
});
