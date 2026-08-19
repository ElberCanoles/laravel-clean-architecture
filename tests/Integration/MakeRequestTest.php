<?php

test('creates form request in presentation layer', function () {
    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'StoreInvoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Requests/StoreInvoiceRequest.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Presentation\Requests;')
        ->toContain('class StoreInvoiceRequest extends FormRequest')
        ->toContain('public function authorize(): bool')
        ->toContain('// TODO: Implement authorization')
        ->toContain('public function rules(): array');
});
