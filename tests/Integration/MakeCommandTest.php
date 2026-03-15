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
        ->toContain('class PayInvoiceCommand');

    $handlerContent = file_get_contents($handlerFile);
    expect($handlerContent)
        ->toContain('namespace App\Billing\Application\Commands\PayInvoice;')
        ->toContain('class PayInvoiceHandler')
        ->toContain('public function handle(PayInvoiceCommand $command): void');
});

test('warns when command files exist without --force', function () {
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice']);

    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice'])
        ->expectsOutputToContain('File already exists');
});
