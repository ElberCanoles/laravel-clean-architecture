<?php

test('wires bindings and routes for a piecemeal entity', function () {
    $this->artisan('clean:context', ['name' => 'Billing']);
    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice']);
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice', '--entity' => 'Invoice']);

    $this->artisan('clean:wire', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('Wiring completed')
        ->assertSuccessful();

    expect(file_get_contents($this->tempDir . '/Billing/Infrastructure/BillingServiceProvider.php'))
        ->toContain('InvoiceWriteRepository::class')
        ->toContain('InvoiceReadEloquentRepository::class');

    expect(file_get_contents($this->tempDir . '/Billing/Presentation/Routes/api.php'))
        ->toContain("Route::apiResource('invoices', InvoiceController::class)");
});

test('wiring is idempotent', function () {
    $this->artisan('clean:context', ['name' => 'Billing']);
    $this->artisan('clean:wire', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();
    $this->artisan('clean:wire', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    $content = file_get_contents($this->tempDir . '/Billing/Infrastructure/BillingServiceProvider.php');
    expect(substr_count($content, 'Repositories\InvoiceWriteRepository::class'))->toBe(1);
});

test('fails cleanly when the context does not exist', function () {
    $this->artisan('clean:wire', ['context' => 'Ghost', 'name' => 'Invoice'])
        ->expectsOutputToContain('does not exist')
        ->assertExitCode(2);
});
