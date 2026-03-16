<?php

test('creates query, handler and read model files', function () {
    $this->artisan('clean:query', ['context' => 'Billing', 'name' => 'ListInvoices'])
        ->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Queries/ListInvoices';

    $queryFile = "$base/ListInvoicesQuery.php";
    $handlerFile = "$base/ListInvoicesHandler.php";
    $readModelFile = "$base/ListInvoicesReadModel.php";

    expect(file_exists($queryFile))->toBeTrue();
    expect(file_exists($handlerFile))->toBeTrue();
    expect(file_exists($readModelFile))->toBeTrue();

    $queryContent = file_get_contents($queryFile);
    expect($queryContent)
        ->toContain('namespace App\Billing\Application\Queries\ListInvoices;')
        ->toContain('readonly class ListInvoicesQuery')
        ->toContain('public string $id,');

    $handlerContent = file_get_contents($handlerFile);
    expect($handlerContent)
        ->toContain('class ListInvoicesHandler')
        ->toContain('public function handle(ListInvoicesQuery $query): ListInvoicesReadModel')
        ->toContain('// TODO: Inject your ReadRepository');

    $readModelContent = file_get_contents($readModelFile);
    expect($readModelContent)
        ->toContain('readonly class ListInvoicesReadModel');
});

test('creates handler with entity injection when --entity is provided', function () {
    $this->artisan('clean:query', [
        'context' => 'Billing',
        'name' => 'ListInvoices',
        '--entity' => 'Invoice',
    ])->assertSuccessful();

    $handlerFile = $this->tempDir . '/Billing/Application/Queries/ListInvoices/ListInvoicesHandler.php';
    $handlerContent = file_get_contents($handlerFile);

    expect($handlerContent)
        ->toContain('use App\Billing\Application\Contracts\InvoiceReadRepository;')
        ->toContain('private readonly InvoiceReadRepository $repository,');
});

test('warns when query files exist without --force', function () {
    $this->artisan('clean:query', ['context' => 'Billing', 'name' => 'ListInvoices']);

    $this->artisan('clean:query', ['context' => 'Billing', 'name' => 'ListInvoices'])
        ->expectsOutputToContain('File already exists');
});
