<?php

test('creates repository interface and eloquent implementation', function () {
    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $interface = $this->tempDir . '/Billing/Domain/Repositories/InvoiceRepository.php';
    $eloquent = $this->tempDir . '/Billing/Infrastructure/InvoiceEloquentRepository.php';

    expect(file_exists($interface))->toBeTrue();
    expect(file_exists($eloquent))->toBeTrue();

    $interfaceContent = file_get_contents($interface);
    expect($interfaceContent)
        ->toContain('namespace App\Billing\Domain\Repositories;')
        ->toContain('interface InvoiceRepository');

    $eloquentContent = file_get_contents($eloquent);
    expect($eloquentContent)
        ->toContain('namespace App\Billing\Infrastructure;')
        ->toContain('class InvoiceEloquentRepository implements InvoiceRepository');
});

test('warns when repository files exist without --force', function () {
    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice']);

    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites repository files with --force', function () {
    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice']);
    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Repository interface created')
        ->expectsOutputToContain('Eloquent repository created');
});
