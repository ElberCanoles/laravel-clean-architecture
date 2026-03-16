<?php

test('creates CQRS repository interfaces, eloquent implementations and mapper', function () {
    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $writeInterface = $this->tempDir . '/Billing/Domain/Repositories/InvoiceWriteRepository.php';
    $readInterface = $this->tempDir . '/Billing/Application/Contracts/InvoiceReadRepository.php';
    $writeEloquent = $this->tempDir . '/Billing/Infrastructure/InvoiceWriteEloquentRepository.php';
    $readEloquent = $this->tempDir . '/Billing/Infrastructure/InvoiceReadEloquentRepository.php';
    $mapper = $this->tempDir . '/Billing/Infrastructure/InvoiceMapper.php';

    expect(file_exists($writeInterface))->toBeTrue();
    expect(file_exists($readInterface))->toBeTrue();
    expect(file_exists($writeEloquent))->toBeTrue();
    expect(file_exists($readEloquent))->toBeTrue();
    expect(file_exists($mapper))->toBeTrue();

    $writeInterfaceContent = file_get_contents($writeInterface);
    expect($writeInterfaceContent)
        ->toContain('namespace App\Billing\Domain\Repositories;')
        ->toContain('interface InvoiceWriteRepository')
        ->toContain('public function save(Invoice $entity): void')
        ->toContain('public function delete(string $id): void');

    $readInterfaceContent = file_get_contents($readInterface);
    expect($readInterfaceContent)
        ->toContain('namespace App\Billing\Application\Contracts;')
        ->toContain('interface InvoiceReadRepository')
        ->toContain('public function findById(string $id): ?InvoiceReadModel')
        ->toContain('public function findAll(): array');

    $writeEloquentContent = file_get_contents($writeEloquent);
    expect($writeEloquentContent)
        ->toContain('namespace App\Billing\Infrastructure;')
        ->toContain('use CleanArchitecture\Support\DispatchesDomainEvents;')
        ->toContain('use App\Billing\Infrastructure\Models\InvoiceModel;')
        ->toContain('class InvoiceWriteEloquentRepository implements InvoiceWriteRepository')
        ->toContain('use DispatchesDomainEvents;')
        ->toContain('$data = InvoiceMapper::toArray($entity)')
        ->toContain('InvoiceModel::updateOrCreate')
        ->toContain('$this->dispatchDomainEvents($entity)')
        ->toContain('InvoiceModel::destroy($id)');

    $readEloquentContent = file_get_contents($readEloquent);
    expect($readEloquentContent)
        ->toContain('namespace App\Billing\Infrastructure;')
        ->toContain('use App\Billing\Infrastructure\Models\InvoiceModel;')
        ->toContain('class InvoiceReadEloquentRepository implements InvoiceReadRepository')
        ->toContain('InvoiceModel::find($id)')
        ->toContain('InvoiceModel::all()');

    $mapperContent = file_get_contents($mapper);
    expect($mapperContent)
        ->toContain('namespace App\Billing\Infrastructure;')
        ->toContain('final class InvoiceMapper')
        ->toContain('public static function toArray(Invoice $entity): array')
        ->toContain('public static function toEntity(InvoiceModel $model): Invoice');
});

test('warns when repository files exist without --force', function () {
    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice']);

    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites repository files with --force', function () {
    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice']);
    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Write repository interface created')
        ->expectsOutputToContain('Read repository interface created')
        ->expectsOutputToContain('Write Eloquent repository created')
        ->expectsOutputToContain('Read Eloquent repository created')
        ->expectsOutputToContain('Mapper created');
});

test('does not create a read model (use clean:read-model instead)', function () {
    $this->artisan('clean:repository', ['context' => 'Billing', 'name' => 'Invoice']);

    $readModel = $this->tempDir . '/Billing/Application/ReadModels/InvoiceReadModel.php';
    expect(file_exists($readModel))->toBeFalse();
});
