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

test('does not duplicate the suffix when the name already carries it', function () {
    $this->artisan('clean:domain-event', ['context' => 'Billing', 'name' => 'InvoicePaidEvent'])
        ->assertSuccessful();

    expect(file_exists($this->tempDir . '/Billing/Domain/Events/InvoicePaidEvent.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Domain/Events/InvoicePaidEventEvent.php'))->toBeFalse();

    expect(file_get_contents($this->tempDir . '/Billing/Domain/Events/InvoicePaidEvent.php'))
        ->toContain('readonly class InvoicePaidEvent');
});
