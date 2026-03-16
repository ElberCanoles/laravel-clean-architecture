<?php

test('creates unit test file with correct content', function () {
    $this->artisan('clean:test', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/tests/Unit/Domain/Billing/InvoiceTest.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('use App\Billing\Domain\Entities\Invoice;')
        ->toContain('it can create a Invoice')
        ->toContain('Invoice::create(')
        ->toContain('it can record and release domain events')
        ->toContain('releaseEvents()');
});

test('uses configured namespace prefix in unit tests', function () {
    config(['clean-architecture.namespace_prefix' => 'Domain']);

    $this->artisan('clean:test', ['context' => 'Sales', 'name' => 'Order'])
        ->assertSuccessful();

    $file = $this->tempDir . '/tests/Unit/Domain/Sales/OrderTest.php';
    $content = file_get_contents($file);

    expect($content)->toContain('use Domain\Sales\Domain\Entities\Order;');
});

test('warns when unit test file exists without --force', function () {
    $this->artisan('clean:test', ['context' => 'Billing', 'name' => 'Invoice']);

    $this->artisan('clean:test', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites unit test with --force', function () {
    $this->artisan('clean:test', ['context' => 'Billing', 'name' => 'Invoice']);
    $this->artisan('clean:test', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Unit test created');
});
