<?php

test('creates read model with correct content', function () {
    $this->artisan('clean:read-model', ['context' => 'Billing', 'name' => 'InvoiceSummary'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Application/ReadModels/InvoiceSummaryReadModel.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Application\ReadModels;')
        ->toContain('readonly class InvoiceSummaryReadModel');
});

test('warns when read model file exists without --force', function () {
    $this->artisan('clean:read-model', ['context' => 'Billing', 'name' => 'InvoiceSummary']);

    $this->artisan('clean:read-model', ['context' => 'Billing', 'name' => 'InvoiceSummary'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites read model with --force', function () {
    $this->artisan('clean:read-model', ['context' => 'Billing', 'name' => 'InvoiceSummary']);
    $this->artisan('clean:read-model', ['context' => 'Billing', 'name' => 'InvoiceSummary', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Read model created');
});
