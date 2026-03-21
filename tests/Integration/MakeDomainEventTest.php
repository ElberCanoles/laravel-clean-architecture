<?php

test('creates domain event with correct content', function () {
    $this->artisan('clean:domain-event', ['context' => 'Billing', 'name' => 'InvoicePaid'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Domain/Events/InvoicePaidEvent.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Domain\Events;')
        ->toContain('readonly class InvoicePaidEvent')
        ->toContain('public string $id,')
        ->toContain('public \DateTimeImmutable $occurredAt');
});

test('warns when domain event file exists without --force', function () {
    $this->artisan('clean:domain-event', ['context' => 'Billing', 'name' => 'InvoicePaid']);

    $this->artisan('clean:domain-event', ['context' => 'Billing', 'name' => 'InvoicePaid'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites domain event with --force', function () {
    $this->artisan('clean:domain-event', ['context' => 'Billing', 'name' => 'InvoicePaid']);
    $this->artisan('clean:domain-event', ['context' => 'Billing', 'name' => 'InvoicePaid', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Domain event created');
});
