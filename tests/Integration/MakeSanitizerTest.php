<?php

test('creates sanitizer in application layer', function () {
    $this->artisan('clean:sanitizer', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Application/Sanitizers/InvoiceSanitizer.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Application\Sanitizers;')
        ->toContain('class InvoiceSanitizer')
        ->toContain('public static function sanitize(array $data): array')
        ->toContain('...$data,');
});
