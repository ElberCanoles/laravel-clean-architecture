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

test('warns when sanitizer exists without --force', function () {
    $this->artisan('clean:sanitizer', ['context' => 'Billing', 'name' => 'Invoice']);

    $this->artisan('clean:sanitizer', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites sanitizer with --force', function () {
    $this->artisan('clean:sanitizer', ['context' => 'Billing', 'name' => 'Invoice']);
    $this->artisan('clean:sanitizer', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Sanitizer created');
});
