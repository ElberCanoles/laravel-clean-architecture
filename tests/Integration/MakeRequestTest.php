<?php

test('creates store and update form requests', function () {
    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful()
        ->expectsOutputToContain('Requests created');

    $store = $this->tempDir . '/Billing/Presentation/Requests/StoreInvoiceRequest.php';
    $update = $this->tempDir . '/Billing/Presentation/Requests/UpdateInvoiceRequest.php';

    expect(file_exists($store))->toBeTrue();
    expect(file_exists($update))->toBeTrue();

    expect(file_get_contents($store))
        ->toContain('namespace Src\Billing\Presentation\Requests;')
        ->toContain('class StoreInvoiceRequest extends FormRequest')
        ->toContain('public function authorize(): bool')
        ->toContain('public function rules(): array')
        ->toContain("'required'");

    expect(file_get_contents($update))
        ->toContain('class UpdateInvoiceRequest extends FormRequest')
        ->toContain("'sometimes'");

    expect($store)->toBeValidPhp();
    expect($update)->toBeValidPhp();
});

test('returns failure when both requests exist without --force', function () {
    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('File already exists')
        ->assertExitCode(1);
});

test('overwrites both requests with --force', function () {
    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    $store = $this->tempDir . '/Billing/Presentation/Requests/StoreInvoiceRequest.php';
    file_put_contents($store, '<?php // stale');

    $this->artisan('clean:request', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents($store))->not->toContain('stale');
});
