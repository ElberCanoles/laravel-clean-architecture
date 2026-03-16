<?php

test('creates query and handler files', function () {
    $this->artisan('clean:query', ['context' => 'Billing', 'name' => 'ListInvoices'])
        ->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Queries/ListInvoices';

    $queryFile = "$base/ListInvoicesQuery.php";
    $handlerFile = "$base/ListInvoicesHandler.php";

    expect(file_exists($queryFile))->toBeTrue();
    expect(file_exists($handlerFile))->toBeTrue();
    expect(file_exists("$base/ListInvoicesReadModel.php"))->toBeFalse();

    $queryContent = file_get_contents($queryFile);
    expect($queryContent)
        ->toContain('namespace App\Billing\Application\Queries\ListInvoices;')
        ->toContain('readonly class ListInvoicesQuery')
        ->toContain('public string $id,');

    $handlerContent = file_get_contents($handlerFile);
    expect($handlerContent)
        ->toContain('class ListInvoicesHandler')
        ->toContain('public function handle(ListInvoicesQuery $query): mixed')
        ->toContain('// TODO: Inject your ReadRepository');
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
        ->toContain('use App\Billing\Application\ReadModels\InvoiceReadModel;')
        ->toContain('private readonly InvoiceReadRepository $repository,')
        ->toContain('public function handle(ListInvoicesQuery $query): InvoiceReadModel');
});

test('warns when query files exist without --force', function () {
    $this->artisan('clean:query', ['context' => 'Billing', 'name' => 'ListInvoices']);

    $this->artisan('clean:query', ['context' => 'Billing', 'name' => 'ListInvoices'])
        ->expectsOutputToContain('File already exists');
});

test('rejects invalid entity name', function () {
    $this->artisan('clean:query', [
        'context' => 'Billing',
        'name' => 'ListInvoices',
        '--entity' => 'bad entity',
    ]);
})->throws(\InvalidArgumentException::class);
