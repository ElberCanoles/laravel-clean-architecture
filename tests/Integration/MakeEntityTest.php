<?php

test('creates entity file with correct content', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Domain/Entities/Invoice.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Domain\Entities;')
        ->toContain('final class Invoice')
        ->toContain('public static function create(string $id): self')
        ->toContain('protected function recordEvent(object $event): void')
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
    $this->artisan('clean:entity', ['context' => 'billing', 'name' => 'Invoice']);
})->throws(\InvalidArgumentException::class);

test('rejects context name with special characters', function () {
    $this->artisan('clean:entity', ['context' => 'My-Context', 'name' => 'Invoice']);
})->throws(\InvalidArgumentException::class);

test('rejects name starting with number', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => '123Entity']);
})->throws(\InvalidArgumentException::class);

test('rejects name with spaces', function () {
    $this->artisan('clean:entity', ['context' => 'Billing', 'name' => 'My Entity']);
})->throws(\InvalidArgumentException::class);
