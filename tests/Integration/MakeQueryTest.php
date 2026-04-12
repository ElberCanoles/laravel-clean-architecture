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
        ->toContain('namespace Src\Billing\Application\Queries\ListInvoices;')
        ->toContain('readonly class ListInvoicesQuery')
        ->toContain('public string $id,');

    $handlerContent = file_get_contents($handlerFile);
    expect($handlerContent)
        ->toContain('class ListInvoicesHandler')
        ->toContain('public function handle(ListInvoicesQuery $query): mixed')
        ->toContain('// TODO: Inject your ReadRepository')
        ->toContain('return null;');
});

test('creates handler with entity injection when --entity is provided', function () {
    $this->artisan('clean:query', [
        'context' => 'Billing',
        'name' => 'GetInvoice',
        '--entity' => 'Invoice',
    ])->assertSuccessful();

    $handlerFile = $this->tempDir . '/Billing/Application/Queries/GetInvoice/GetInvoiceHandler.php';
    $handlerContent = file_get_contents($handlerFile);

    expect($handlerContent)
        ->toContain('use Src\Billing\Application\Contracts\InvoiceReadRepository;')
        ->toContain('use Src\Billing\Application\ReadModels\InvoiceReadModel;')
        ->toContain('private readonly InvoiceReadRepository $repository,')
        ->toContain('public function handle(GetInvoiceQuery $query): ?InvoiceReadModel')
        ->toContain('return $this->repository->findById($query->id);');
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

test('creates collection query with --collection flag', function () {
    $this->artisan('clean:query', ['context' => 'Billing', 'name' => 'ListInvoices', '--collection' => true])
        ->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Queries/ListInvoices';

    $queryFile = "$base/ListInvoicesQuery.php";
    $handlerFile = "$base/ListInvoicesHandler.php";

    expect(file_exists($queryFile))->toBeTrue();
    expect(file_exists($handlerFile))->toBeTrue();

    $queryContent = file_get_contents($queryFile);
    expect($queryContent)
        ->toContain('readonly class ListInvoicesQuery')
        ->toContain('public int $page = 1,')
        ->toContain('public int $perPage = 15,')
        ->not->toContain('public string $id,');

    $handlerContent = file_get_contents($handlerFile);
    expect($handlerContent)
        ->toContain('public function handle(ListInvoicesQuery $query): array')
        ->toContain('return [];');
});

test('creates collection query with entity injection', function () {
    $this->artisan('clean:query', [
        'context' => 'Billing',
        'name' => 'ListInvoices',
        '--entity' => 'Invoice',
        '--collection' => true,
    ])->assertSuccessful();

    $handlerFile = $this->tempDir . '/Billing/Application/Queries/ListInvoices/ListInvoicesHandler.php';
    $handlerContent = file_get_contents($handlerFile);

    expect($handlerContent)
        ->toContain('use Src\Billing\Application\Contracts\InvoiceReadRepository;')
        ->toContain('use CleanArchitecture\Support\PaginatedResult;')
        ->toContain('private readonly InvoiceReadRepository $repository,')
        ->toContain('public function handle(ListInvoicesQuery $query): PaginatedResult')
        ->toContain('return $this->repository->findAll($query->page, $query->perPage);');
});
