<?php

test('creates mapper in infrastructure layer', function () {
    $this->artisan('clean:mapper', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Infrastructure/InvoiceMapper.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Infrastructure;')
        ->toContain('use Src\Billing\Infrastructure\Models\InvoiceModel;')
        ->toContain('final class InvoiceMapper')
        ->toContain('public static function toArray(Invoice $entity): array')
        ->toContain('public static function toEntity(InvoiceModel $model): Invoice');
});

test('warns when mapper exists without --force', function () {
    $this->artisan('clean:mapper', ['context' => 'Billing', 'name' => 'Invoice']);

    $this->artisan('clean:mapper', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites mapper with --force', function () {
    $this->artisan('clean:mapper', ['context' => 'Billing', 'name' => 'Invoice']);
    $this->artisan('clean:mapper', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Mapper created');
});
