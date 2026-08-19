<?php

test('creates specification with correct content', function () {
    $this->artisan('clean:specification', ['context' => 'Billing', 'name' => 'InvoiceOverdue'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Domain/Specifications/InvoiceOverdueSpecification.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Domain\Specifications;')
        ->toContain('use CleanArchitecture\Support\CompositeSpecification;')
        ->toContain('class InvoiceOverdueSpecification extends CompositeSpecification')
        ->toContain('public function isSatisfiedBy(mixed $candidate): bool')
        // Composition now lives in the shared CompositeSpecification base —
        // the broken anonymous-class and()/or()/not() are gone from the stub.
        ->not->toContain('new class');
});
