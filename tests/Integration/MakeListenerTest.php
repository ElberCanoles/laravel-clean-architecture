<?php

test('wires the event type hint with --event', function () {
    $this->artisan('clean:listener', [
        'context' => 'Billing',
        'name' => 'SendInvoiceMail',
        '--event' => 'InvoicePaid',
    ])->assertSuccessful();

    $content = file_get_contents($this->tempDir . '/Billing/Application/Listeners/SendInvoiceMailListener.php');

    expect($content)
        ->toContain('use Src\Billing\Domain\Events\InvoicePaidEvent;')
        ->toContain('public function handle(InvoicePaidEvent $event): void')
        ->toContain('Event::listen(InvoicePaidEvent::class, SendInvoiceMailListener::class);')
        // Listeners live in Application — the framework must stay out.
        ->not->toContain('Illuminate');
});

test('defaults to a generic object hint without --event', function () {
    $this->artisan('clean:listener', ['context' => 'Billing', 'name' => 'SendInvoiceMail'])
        ->assertSuccessful();

    $content = file_get_contents($this->tempDir . '/Billing/Application/Listeners/SendInvoiceMailListener.php');

    expect($content)->toContain('public function handle(object $event): void');
});

test('does not double the Event suffix in --event', function () {
    $this->artisan('clean:listener', [
        'context' => 'Billing',
        'name' => 'Audit',
        '--event' => 'InvoicePaidEvent',
    ])->assertSuccessful();

    expect(file_get_contents($this->tempDir . '/Billing/Application/Listeners/AuditListener.php'))
        ->toContain('handle(InvoicePaidEvent $event)')
        ->not->toContain('InvoicePaidEventEvent');
});
