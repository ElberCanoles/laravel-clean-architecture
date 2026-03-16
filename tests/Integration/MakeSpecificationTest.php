<?php

test('creates specification with correct content', function () {
    $this->artisan('clean:specification', ['context' => 'Billing', 'name' => 'InvoiceOverdue'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Domain/Specifications/InvoiceOverdueSpecification.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace App\Billing\Domain\Specifications;')
        ->toContain('class InvoiceOverdueSpecification')
        ->toContain('public function isSatisfiedBy(mixed $candidate): bool')
        ->toContain('public function and(self $other): static')
        ->toContain('public function or(self $other): static')
        ->toContain('public function not(): static');
});

test('warns when specification file exists without --force', function () {
    $this->artisan('clean:specification', ['context' => 'Billing', 'name' => 'InvoiceOverdue']);

    $this->artisan('clean:specification', ['context' => 'Billing', 'name' => 'InvoiceOverdue'])
        ->expectsOutputToContain('File already exists');
});
