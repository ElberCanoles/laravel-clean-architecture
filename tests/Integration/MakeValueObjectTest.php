<?php

test('creates value object with correct content', function () {
    $this->artisan('clean:value-object', ['context' => 'Billing', 'name' => 'Money'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Domain/ValueObjects/Money.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace App\Billing\Domain\ValueObjects;')
        ->toContain('readonly class Money')
        ->toContain("throw new \\InvalidArgumentException('Money cannot be empty.')")
        ->toContain('public function equals(self $other): bool')
        ->toContain('public function __toString(): string');
});

test('warns when value object file exists without --force', function () {
    $this->artisan('clean:value-object', ['context' => 'Billing', 'name' => 'Money']);

    $this->artisan('clean:value-object', ['context' => 'Billing', 'name' => 'Money'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites value object with --force', function () {
    $this->artisan('clean:value-object', ['context' => 'Billing', 'name' => 'Money']);
    $this->artisan('clean:value-object', ['context' => 'Billing', 'name' => 'Money', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Value object created');
});

test('rejects invalid context name', function () {
    $this->artisan('clean:value-object', ['context' => 'invalid-context', 'name' => 'Money']);
})->throws(\InvalidArgumentException::class);

test('rejects invalid name', function () {
    $this->artisan('clean:value-object', ['context' => 'Billing', 'name' => '123Money']);
})->throws(\InvalidArgumentException::class);
