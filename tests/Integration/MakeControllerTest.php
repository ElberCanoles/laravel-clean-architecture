<?php

test('creates controller in presentation layer', function () {
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Controllers/InvoiceController.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace App\Billing\Presentation\Controllers;')
        ->toContain('use App\Billing\Presentation\Requests\StoreInvoiceRequest;')
        ->toContain('use App\Billing\Presentation\Resources\InvoiceResource;')
        ->toContain('class InvoiceController extends Controller')
        ->toContain('public function __construct(')
        ->toContain('public function index(): JsonResponse')
        ->toContain('public function show(string $id): JsonResponse')
        ->toContain('public function store(StoreInvoiceRequest $request): JsonResponse')
        ->toContain('public function update(StoreInvoiceRequest $request, string $id): JsonResponse')
        ->toContain('public function destroy(string $id): JsonResponse');
});

test('warns when controller exists without --force', function () {
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice']);

    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('File already exists');
});

test('overwrites controller with --force', function () {
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice']);
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Controller created');
});
