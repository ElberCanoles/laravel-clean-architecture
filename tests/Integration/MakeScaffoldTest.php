<?php

test('scaffolds all files for an entity', function () {
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    // Entity
    expect(file_exists($this->tempDir . '/Billing/Domain/Entities/Invoice.php'))->toBeTrue();

    // Repository interfaces + implementations + mapper
    expect(file_exists($this->tempDir . '/Billing/Domain/Repositories/InvoiceWriteRepository.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Application/Contracts/InvoiceReadRepository.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Infrastructure/InvoiceWriteEloquentRepository.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Infrastructure/InvoiceReadEloquentRepository.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Infrastructure/InvoiceMapper.php'))->toBeTrue();

    // Read model
    expect(file_exists($this->tempDir . '/Billing/Application/ReadModels/InvoiceReadModel.php'))->toBeTrue();

    // Command + handler
    expect(file_exists($this->tempDir . '/Billing/Application/Commands/CreateInvoice/CreateInvoiceCommand.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Application/Commands/CreateInvoice/CreateInvoiceHandler.php'))->toBeTrue();

    // Query + handler (ReadModel lives in Application/ReadModels, not in Query folder)
    expect(file_exists($this->tempDir . '/Billing/Application/Queries/GetInvoice/GetInvoiceQuery.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Application/Queries/GetInvoice/GetInvoiceHandler.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Application/Queries/GetInvoice/GetInvoiceReadModel.php'))->toBeFalse();

    // Controller, request, resource, sanitizer
    expect(file_exists($this->tempDir . '/Billing/Presentation/Controllers/InvoiceController.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Presentation/Requests/InvoiceRequest.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Presentation/Resources/InvoiceResource.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Application/Sanitizers/InvoiceSanitizer.php'))->toBeTrue();

    // Unit test
    expect(file_exists($this->tempDir . '/tests/Unit/Domain/Billing/InvoiceTest.php'))->toBeTrue();
});

test('scaffold passes --force to sub-commands', function () {
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice']);
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Scaffold for [Invoice] in [Billing] created successfully');
});

test('scaffold wires entity injection in command handler', function () {
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice']);

    $handlerFile = $this->tempDir . '/Billing/Application/Commands/CreateInvoice/CreateInvoiceHandler.php';
    $content = file_get_contents($handlerFile);

    expect($content)
        ->toContain('use App\Billing\Domain\Repositories\InvoiceWriteRepository;')
        ->toContain('private readonly InvoiceWriteRepository $repository,');
});

test('scaffold wires entity injection in query handler', function () {
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice']);

    $handlerFile = $this->tempDir . '/Billing/Application/Queries/GetInvoice/GetInvoiceHandler.php';
    $content = file_get_contents($handlerFile);

    expect($content)
        ->toContain('use App\Billing\Application\Contracts\InvoiceReadRepository;')
        ->toContain('use App\Billing\Application\ReadModels\InvoiceReadModel;')
        ->toContain('private readonly InvoiceReadRepository $repository,')
        ->toContain('public function handle(GetInvoiceQuery $query): InvoiceReadModel');
});

test('scaffold wires controller with CQRS imports and handlers', function () {
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice']);

    $file = $this->tempDir . '/Billing/Presentation/Controllers/InvoiceController.php';
    $content = file_get_contents($file);

    expect($content)
        ->toContain('use App\Billing\Application\Commands\CreateInvoice\CreateInvoiceHandler;')
        ->toContain('use App\Billing\Application\Queries\GetInvoice\GetInvoiceHandler;')
        ->toContain('private readonly CreateInvoiceHandler $createHandler,')
        ->toContain('private readonly GetInvoiceHandler $getHandler,')
        ->toContain('$this->getHandler->handle(new GetInvoiceQuery($id))')
        ->toContain('InvoiceSanitizer::sanitize($request->validated())')
        ->toContain('$this->createHandler->handle(new CreateInvoiceCommand(...$sanitized))');
});

test('scaffold wires service provider bindings', function () {
    $this->artisan('clean:context', ['name' => 'Billing']);
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true]);

    $spFile = $this->tempDir . '/Billing/Infrastructure/BillingServiceProvider.php';
    $content = file_get_contents($spFile);

    expect($content)
        ->toContain('InvoiceWriteRepository::class')
        ->toContain('InvoiceWriteEloquentRepository::class')
        ->toContain('InvoiceReadRepository::class')
        ->toContain('InvoiceReadEloquentRepository::class')
        ->not->toContain('TODO: Bind repository interfaces');
});

test('scaffold wires routes with plural kebab-case', function () {
    $this->artisan('clean:context', ['name' => 'Billing']);
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true]);

    $routeFile = $this->tempDir . '/Billing/Presentation/Routes/api.php';
    $content = file_get_contents($routeFile);

    expect($content)
        ->toContain("Route::apiResource('invoices', InvoiceController::class)")
        ->toContain('use App\Billing\Presentation\Controllers\InvoiceController;');
});

test('scaffold warns when service provider has no markers', function () {
    $this->artisan('clean:context', ['name' => 'Billing']);

    // Remove markers from SP
    $spFile = $this->tempDir . '/Billing/Infrastructure/BillingServiceProvider.php';
    $content = file_get_contents($spFile);
    $content = str_replace('// {bindings}', '', $content);
    $content = str_replace('// {/bindings}', '', $content);
    file_put_contents($spFile, $content);

    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->expectsOutputToContain('No binding markers');
});

test('scaffold skips wiring when no bounded context exists', function () {
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful()
        ->expectsOutputToContain('ServiceProvider not found');
});

test('scaffold does not duplicate bindings on re-run', function () {
    $this->artisan('clean:context', ['name' => 'Billing']);
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true]);
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true]);

    $spFile = $this->tempDir . '/Billing/Infrastructure/BillingServiceProvider.php';
    $content = file_get_contents($spFile);

    expect(substr_count($content, 'InvoiceWriteRepository::class'))->toBe(1);
});
