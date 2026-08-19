<?php

test('creates value object with correct content', function () {
    $this->artisan('clean:value-object', ['context' => 'Billing', 'name' => 'Money'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Domain/ValueObjects/Money.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Domain\ValueObjects;')
        ->toContain('readonly class Money')
        ->toContain("throw new \\InvalidArgumentException('Money cannot be empty.')")
        ->toContain('public function equals(self $other): bool')
        ->toContain('public function __toString(): string');
});

test('normalizes context name to StudlyCase', function () {
    $this->artisan('clean:value-object', ['context' => 'shared-kernel', 'name' => 'Money'])
        ->assertSuccessful();

    expect(file_exists($this->tempDir . '/SharedKernel/Domain/ValueObjects/Money.php'))->toBeTrue();
});

test('rejects invalid name', function () {
    $this->artisan('clean:value-object', ['context' => 'Billing', 'name' => '123Money'])
        ->expectsOutputToContain('Invalid name')
        ->assertExitCode(2);
});
