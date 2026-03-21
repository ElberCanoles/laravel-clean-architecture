<?php

test('creates domain exception with correct content', function () {
    $this->artisan('clean:exception', ['context' => 'Billing', 'name' => 'InvoiceNotFound'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Domain/Exceptions/InvoiceNotFoundException.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Domain\Exceptions;')
        ->toContain('class InvoiceNotFoundException extends \DomainException')
        ->toContain('public static function withMessage(string $message): self');
});

test('warns when domain exception file exists without --force', function () {
    $this->artisan('clean:exception', ['context' => 'Billing', 'name' => 'InvoiceNotFound']);

    $this->artisan('clean:exception', ['context' => 'Billing', 'name' => 'InvoiceNotFound'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites domain exception with --force', function () {
    $this->artisan('clean:exception', ['context' => 'Billing', 'name' => 'InvoiceNotFound']);
    $this->artisan('clean:exception', ['context' => 'Billing', 'name' => 'InvoiceNotFound', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Domain exception created');
});
