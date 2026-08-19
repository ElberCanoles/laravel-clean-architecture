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

test('warns when entity file already exists without --force', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice']);

    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites entity file with --force', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice']);
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Entity created');
});

test('rejects lowercase context name', function () {
    $this->artisan('clean:entity', ['context' => 'billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('Invalid context')
        ->assertExitCode(2);
});

test('rejects context name with special characters', function () {
    $this->artisan('clean:entity', ['context' => 'My-Context', 'name' => 'Invoice'])
        ->expectsOutputToContain('Invalid context')
        ->assertExitCode(2);
});

test('rejects name starting with number', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => '123Entity'])
        ->expectsOutputToContain('Invalid name')
        ->assertExitCode(2);
});

test('rejects name with spaces', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'My Entity'])
        ->expectsOutputToContain('Invalid name')
        ->assertExitCode(2);
});

test('rejects PHP reserved words as names', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'List'])
        ->expectsOutputToContain('is a PHP reserved word')
        ->assertExitCode(2);

    expect(file_exists($this->tempDir . '/Billing/Domain/Entities/List.php'))->toBeFalse();
});

test('returns failure exit code when file exists without --force', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('File already exists')
        ->assertExitCode(1);
});

test('overwriting with --force actually changes file content', function () {
    $file = $this->tempDir . '/Billing/Domain/Entities/Invoice.php';

    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();
    file_put_contents($file, '<?php // stale content');

    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful();

    expect(file_get_contents($file))
        ->not->toContain('stale content')
        ->toContain('final class Invoice');
});
