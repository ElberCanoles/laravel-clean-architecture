<?php

test('creates form request in presentation layer', function () {
    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'StoreInvoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Requests/StoreInvoiceRequest.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace App\Billing\Presentation\Requests;')
        ->toContain('class StoreInvoiceRequest extends FormRequest')
        ->toContain('public function authorize(): bool')
        ->toContain('public function rules(): array');
});

test('warns when request exists without --force', function () {
    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'StoreInvoice']);

    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'StoreInvoice'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites request with --force', function () {
    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'StoreInvoice']);
    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'StoreInvoice', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Request created');
});
