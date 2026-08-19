<?php

use Illuminate\Support\Facades\File;

test('destroys every scaffolded file with --force', function () {
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    $this->artisan('clean:destroy', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful();

    expect(file_exists($this->tempDir . '/Billing/Domain/Entities/Invoice.php'))->toBeFalse();
    expect(file_exists($this->tempDir . '/Billing/Presentation/Controllers/InvoiceController.php'))->toBeFalse();
    expect(file_exists($this->tempDir . '/Billing/Infrastructure/InMemoryInvoiceWriteRepository.php'))->toBeFalse();

    // Without --with-migration the migration survives.
    expect(File::glob(database_path('migrations') . '/*_create_invoices_table.php'))->not->toBeEmpty();
});

test('also deletes the migration with --with-migration', function () {
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    $this->artisan('clean:destroy', ['context' => 'Billing', 'name' => 'Invoice', '--with-migration' => true, '--force' => true])
        ->assertSuccessful();

    expect(File::glob(database_path('migrations') . '/*_create_invoices_table.php'))->toBeEmpty();
});

test('asks for confirmation without --force and aborts on no', function () {
    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    $this->artisan('clean:destroy', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsConfirmation('Delete 27 file(s) generated for [Invoice] in [Billing]?', 'no')
        ->expectsOutputToContain('Aborted')
        ->assertSuccessful();

    expect(file_exists($this->tempDir . '/Billing/Domain/Entities/Invoice.php'))->toBeTrue();
});

test('reports when there is nothing to destroy', function () {
    $this->artisan('clean:destroy', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->expectsOutputToContain('Nothing to destroy')
        ->assertSuccessful();
});
