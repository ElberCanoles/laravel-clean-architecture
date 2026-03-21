<?php

test('creates model in infrastructure models directory', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Infrastructure/Models/InvoiceModel.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Infrastructure\Models;')
        ->toContain('use Illuminate\Database\Eloquent\Concerns\HasUuids;')
        ->toContain('use Illuminate\Database\Eloquent\Model;')
        ->toContain('class InvoiceModel extends Model')
        ->toContain('use HasUuids;')
        ->toContain("protected \$table = 'invoices';")
        ->toContain("'id',");
});

test('generates correct table name for multi-word entities', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'OrderItem'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Infrastructure/Models/OrderItemModel.php';
    $content = file_get_contents($file);

    expect($content)
        ->toContain("protected \$table = 'order_items';")
        ->toContain('class OrderItemModel extends Model');
});

test('warns when model exists without --force', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice']);

    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites model with --force', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice']);
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Model created');
});

test('rejects invalid name', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'bad-name']);
})->throws(\InvalidArgumentException::class);

test('rejects invalid context', function () {
    $this->artisan('clean:model', ['context' => 'billing', 'name' => 'Invoice']);
})->throws(\InvalidArgumentException::class);
