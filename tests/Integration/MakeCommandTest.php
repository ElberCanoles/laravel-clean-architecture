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

test('creates create command with --crud=create', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'CreateInvoice',
        '--entity' => 'Invoice',
        '--crud' => 'create',
    ])->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Commands/CreateInvoice';

    $commandContent = file_get_contents("$base/CreateInvoiceCommand.php");
    expect($commandContent)
        ->toContain('public array $data,')
        ->not->toContain('public string $id,');

    $handlerContent = file_get_contents("$base/CreateInvoiceHandler.php");
    expect($handlerContent)
        ->toContain('use App\Billing\Domain\Entities\Invoice;')
        ->toContain('use Illuminate\Support\Str;')
        ->toContain('Invoice::create(Str::uuid()->toString())')
        ->toContain('$this->repository->save($entity);');
});

test('creates update command with --crud=update', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'UpdateInvoice',
        '--entity' => 'Invoice',
        '--crud' => 'update',
    ])->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Commands/UpdateInvoice';

    $commandContent = file_get_contents("$base/UpdateInvoiceCommand.php");
    expect($commandContent)
        ->toContain('public string $id,')
        ->toContain('public array $data,');

    $handlerContent = file_get_contents("$base/UpdateInvoiceHandler.php");
    expect($handlerContent)
        ->toContain('// TODO: Load entity, apply changes from $command->data, persist via repository');
});

test('creates delete command with --crud=delete', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'DeleteInvoice',
        '--entity' => 'Invoice',
        '--crud' => 'delete',
    ])->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Commands/DeleteInvoice';

    $commandContent = file_get_contents("$base/DeleteInvoiceCommand.php");
    expect($commandContent)
        ->toContain('public string $id,')
        ->not->toContain('public array $data,');

    $handlerContent = file_get_contents("$base/DeleteInvoiceHandler.php");
    expect($handlerContent)
        ->toContain('$this->repository->delete($command->id);');
});

test('rejects invalid --crud value', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'PayInvoice',
        '--crud' => 'invalid',
    ]);
})->throws(\InvalidArgumentException::class);

test('rejects invalid entity name', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'PayInvoice',
        '--entity' => 'invalid-entity',
    ]);
})->throws(\InvalidArgumentException::class);
