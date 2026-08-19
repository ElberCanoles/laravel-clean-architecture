<?php

test('creates unit test file with correct content', function () {
    $this->artisan('clean:test', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/tests/Unit/Domain/Billing/InvoiceTest.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('use Src\Billing\Domain\Entities\Invoice;')
        ->toContain('use Src\Billing\Domain\Events\InvoiceCreatedEvent;')
        ->toContain('it can create a Invoice')
        ->toContain('Invoice::create(')
        ->toContain('creating a Invoice records a creation event')
        ->toContain('toBeInstanceOf(InvoiceCreatedEvent::class)')
        ->toContain('releasing events clears them')
        ->toContain('entities with the same id are equal');
});

test('uses configured namespace prefix in unit tests', function () {
    config(['clean-architecture.namespace_prefix' => 'Domain']);

    $this->artisan('clean:test', ['context' => 'Sales', 'name' => 'Order'])
        ->assertSuccessful();

    $file = $this->tempDir . '/tests/Unit/Domain/Sales/OrderTest.php';
    $content = file_get_contents($file);

    expect($content)->toContain('use Domain\Sales\Domain\Entities\Order;');
});

test('generates a handler test against the in-memory repository with --handler', function () {
    $this->artisan('clean:test', ['context' => 'Billing', 'name' => 'Invoice', '--handler' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Handler test created');

    $file = $this->tempDir . '/tests/Unit/Application/Billing/InvoiceHandlersTest.php';
    expect(file_exists($file))->toBeTrue();
    expect($file)->toBeValidPhp();

    expect(file_get_contents($file))
        ->toContain('use Src\Billing\Infrastructure\InMemoryInvoiceWriteRepository;')
        ->toContain('new CreateInvoiceHandler($repository)')
        ->toContain('toThrow(InvoiceNotFound::class)')
        ->toContain('toBeInstanceOf(InvoiceCreatedEvent::class)');
});

test('generates an HTTP feature test with --feature', function () {
    $this->artisan('clean:test', ['context' => 'Billing', 'name' => 'Invoice', '--feature' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Feature test created');

    $file = $this->tempDir . '/tests/Feature/Billing/InvoiceApiTest.php';
    expect(file_exists($file))->toBeTrue();
    expect($file)->toBeValidPhp();

    expect(file_get_contents($file))
        ->toContain("postJson('/billing/invoices'")
        ->toContain('assertCreated()')
        ->toContain("assertHeader('Location')")
        ->toContain("'last_page'");
});

test('rejects combining --handler and --feature', function () {
    $this->artisan('clean:test', ['context' => 'Billing', 'name' => 'Invoice', '--handler' => true, '--feature' => true])
        ->expectsOutputToContain('not both')
        ->assertExitCode(2);
});
