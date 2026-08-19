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

test('creates model with HasUlids when --id-type=ulid', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice', '--id-type' => 'ulid'])
        ->assertSuccessful();

    $content = file_get_contents($this->tempDir . '/Billing/Infrastructure/Models/InvoiceModel.php');

    expect($content)
        ->toContain('use Illuminate\Database\Eloquent\Concerns\HasUlids;')
        ->toContain('use HasUlids;')
        ->not->toContain('HasUuids');
});

test('creates model with HasUuids when --id-type=uuid', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice', '--id-type' => 'uuid'])
        ->assertSuccessful();

    $content = file_get_contents($this->tempDir . '/Billing/Infrastructure/Models/InvoiceModel.php');

    expect($content)
        ->toContain('use HasUuids;')
        ->not->toContain('HasUlids');
});

test('model honours id_type config when no option is given', function () {
    config()->set('clean-architecture.id_type', 'ulid');

    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $content = file_get_contents($this->tempDir . '/Billing/Infrastructure/Models/InvoiceModel.php');

    expect($content)->toContain('use HasUlids;');
});

test('--id-type option overrides id_type config', function () {
    config()->set('clean-architecture.id_type', 'ulid');

    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice', '--id-type' => 'uuid'])
        ->assertSuccessful();

    $content = file_get_contents($this->tempDir . '/Billing/Infrastructure/Models/InvoiceModel.php');

    expect($content)->toContain('use HasUuids;');
});

test('rejects invalid --id-type value', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice', '--id-type' => 'snowflake'])
        ->expectsOutputToContain("Invalid id type: 'snowflake'")
        ->assertExitCode(2);
});

test('rejects invalid id_type config value', function () {
    config()->set('clean-architecture.id_type', 'snowflake');

    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain("Invalid id type: 'snowflake'")
        ->assertExitCode(2);
});

test('rejects invalid name', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'bad-name'])
        ->expectsOutputToContain("Invalid name")
        ->assertExitCode(2);
});

test('rejects invalid context', function () {
    $this->artisan('clean:model', ['context' => 'billing', 'name' => 'Invoice'])
        ->expectsOutputToContain("Invalid context")
        ->assertExitCode(2);
});
