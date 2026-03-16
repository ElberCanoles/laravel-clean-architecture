<?php

test('creates command and handler files', function () {
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice'])
        ->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Commands/PayInvoice';

    $commandFile = "$base/PayInvoiceCommand.php";
    $handlerFile = "$base/PayInvoiceHandler.php";

    expect(file_exists($commandFile))->toBeTrue();
    expect(file_exists($handlerFile))->toBeTrue();

    $commandContent = file_get_contents($commandFile);
    expect($commandContent)
        ->toContain('namespace App\Billing\Application\Commands\PayInvoice;')
        ->toContain('readonly class PayInvoiceCommand')
        ->toContain('public string $id,');

    $handlerContent = file_get_contents($handlerFile);
    expect($handlerContent)
        ->toContain('namespace App\Billing\Application\Commands\PayInvoice;')
        ->toContain('class PayInvoiceHandler')
        ->toContain('public function handle(PayInvoiceCommand $command): void')
        ->toContain('// TODO: Inject your WriteRepository');
});

test('creates handler with entity injection when --entity is provided', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'PayInvoice',
        '--entity' => 'Invoice',
    ])->assertSuccessful();

    $handlerFile = $this->tempDir . '/Billing/Application/Commands/PayInvoice/PayInvoiceHandler.php';
    $handlerContent = file_get_contents($handlerFile);

    expect($handlerContent)
        ->toContain('use App\Billing\Domain\Repositories\InvoiceWriteRepository;')
        ->toContain('private readonly InvoiceWriteRepository $repository,');
});

test('warns when command files exist without --force', function () {
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice']);

    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice'])
        ->expectsOutputToContain('File already exists');
});

test('rejects invalid entity name', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'PayInvoice',
        '--entity' => 'invalid-entity',
    ]);
})->throws(\InvalidArgumentException::class);
