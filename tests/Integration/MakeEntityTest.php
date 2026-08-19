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
        ->toContain('public static function fromPersistence(string $id): self')
        ->toContain('private function recordEvent(object $event): void')
        ->toContain('public function releaseEvents(): array');
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
