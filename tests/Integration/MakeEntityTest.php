<?php

test('creates entity file with correct content', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Domain/Entities/Invoice.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Domain\Entities;')
        ->toContain('use CleanArchitecture\Support\HasDomainEvents;')
        ->toContain('final class Invoice implements HasDomainEvents')
        ->toContain('private function __construct(')
        ->toContain('public static function create(string $id): self')
        ->toContain('use Src\Billing\Domain\Events\InvoiceCreatedEvent;')
        ->toContain('$entity->recordEvent(new InvoiceCreatedEvent($id));')
        ->toContain('public static function fromPersistence(string $id): self')
        ->toContain('public function equals(self $other): bool')
        ->toContain('private function recordEvent(object $event): void')
        ->toContain('public function releaseEvents(): array');

    // The referenced creation event is generated alongside the entity.
    expect(file_exists($this->tempDir . '/Billing/Domain/Events/InvoiceCreatedEvent.php'))->toBeTrue();
});

test('does not overwrite an existing creation event', function () {
    $eventFile = $this->tempDir . '/Billing/Domain/Events/InvoiceCreatedEvent.php';

    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();
    file_put_contents($eventFile, '<?php // customized by the user');

    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents($eventFile))->toBe('<?php // customized by the user');
});

test('normalizes lowercase context to StudlyCase', function () {
    $this->artisan('clean:entity', ['context' => 'billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    expect(file_exists($this->tempDir . '/Billing/Domain/Entities/Invoice.php'))->toBeTrue();
});

test('normalizes kebab-case context to StudlyCase', function () {
    $this->artisan('clean:entity', ['context' => 'my-context', 'name' => 'Invoice'])
        ->assertSuccessful();

    expect(file_exists($this->tempDir . '/MyContext/Domain/Entities/Invoice.php'))->toBeTrue();
});

test('rejects name starting with number', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => '123Entity'])
        ->expectsOutputToContain('Invalid name')
        ->assertExitCode(2);
});

test('normalizes names with spaces or underscores', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'my entity'])
        ->assertSuccessful();
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'line_item'])
        ->assertSuccessful();

    expect(file_exists($this->tempDir . '/Billing/Domain/Entities/MyEntity.php'))->toBeTrue();
    expect(file_exists($this->tempDir . '/Billing/Domain/Entities/LineItem.php'))->toBeTrue();
});

test('rejects PHP reserved words as names', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'List'])
        ->expectsOutputToContain('is a PHP reserved word')
        ->assertExitCode(2);

    expect(file_exists($this->tempDir . '/Billing/Domain/Entities/List.php'))->toBeFalse();
});
