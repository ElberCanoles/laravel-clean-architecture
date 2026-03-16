<?php

test('creates controller in presentation layer', function () {
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Controllers/InvoiceController.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace App\Billing\Presentation\Controllers;')
        ->toContain('use App\Billing\Presentation\Requests\InvoiceRequest;')
        ->toContain('use App\Billing\Presentation\Resources\InvoiceResource;')
        ->toContain('class InvoiceController extends Controller')
        ->toContain('public function __construct(')
        ->toContain('public function index(): JsonResponse')
        ->toContain('public function show(string $id): JsonResponse')
        ->toContain('public function store(InvoiceRequest $request): JsonResponse')
        ->toContain('public function update(InvoiceRequest $request, string $id): JsonResponse')
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

test('controller without --entity keeps TODO comments', function () {
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Controllers/InvoiceController.php';
    $content = file_get_contents($file);

    expect($content)
        ->toContain('// TODO: Inject command/query handlers')
        ->toContain('// TODO: Implement show query')
        ->toContain('// TODO: Implement create command');
});

test('controller with --entity wires CQRS handlers', function () {
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice', '--entity' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Controllers/InvoiceController.php';
    $content = file_get_contents($file);

    expect($content)
        ->toContain('use App\Billing\Application\Commands\CreateInvoice\CreateInvoiceCommand;')
        ->toContain('use App\Billing\Application\Commands\CreateInvoice\CreateInvoiceHandler;')
        ->toContain('use App\Billing\Application\Queries\GetInvoice\GetInvoiceHandler;')
        ->toContain('use App\Billing\Application\Queries\GetInvoice\GetInvoiceQuery;')
        ->toContain('use App\Billing\Application\Sanitizers\InvoiceSanitizer;')
        ->toContain('private readonly CreateInvoiceHandler $createHandler,')
        ->toContain('private readonly GetInvoiceHandler $getHandler,')
        ->toContain('$this->getHandler->handle(new GetInvoiceQuery($id))')
        ->toContain('InvoiceSanitizer::sanitize($request->validated())')
        ->toContain('$this->createHandler->handle(new CreateInvoiceCommand(...$sanitized))');
});
