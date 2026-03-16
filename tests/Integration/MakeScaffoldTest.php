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
