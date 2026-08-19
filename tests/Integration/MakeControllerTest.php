<?php

test('creates controller in presentation layer', function () {
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Controllers/InvoiceController.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Presentation\Controllers;')
        ->toContain('use Src\Billing\Presentation\Requests\StoreInvoiceRequest;')
        ->toContain('use Src\Billing\Presentation\Requests\UpdateInvoiceRequest;')
        ->toContain('use Src\Billing\Presentation\Resources\InvoiceResource;')
        ->toContain('class InvoiceController extends Controller')
        ->toContain('public function __construct(')
        ->toContain('use Illuminate\Http\Request;')
        ->toContain('public function index(Request $request): JsonResponse')
        ->toContain('public function show(string $id): JsonResponse')
        ->toContain('public function store(StoreInvoiceRequest $request): JsonResponse')
        ->toContain('public function update(UpdateInvoiceRequest $request, string $id): Response')
        ->toContain('public function destroy(string $id): Response');
});

test('controller without --entity keeps TODO comments', function () {
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Controllers/InvoiceController.php';
    $content = file_get_contents($file);

    expect($content)
        ->toContain('// TODO: Inject command/query handlers')
        ->toContain("// TODO: Implement list query using \$request->query('page')")
        ->toContain('// TODO: Implement show query')
        ->toContain('// TODO: Implement create command')
        ->toContain('// TODO: Implement update command')
        ->toContain('// TODO: Implement delete command');
});

test('controller with --entity wires all CQRS handlers', function () {
    $this->artisan('clean:controller', ['context' => 'Billing', 'name' => 'Invoice', '--entity' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Controllers/InvoiceController.php';
    $content = file_get_contents($file);

    expect($content)
        // Imports
        ->toContain('use Src\Billing\Application\Commands\CreateInvoice\CreateInvoiceCommand;')
        ->toContain('use Src\Billing\Application\Commands\CreateInvoice\CreateInvoiceHandler;')
        ->toContain('use Src\Billing\Application\Commands\UpdateInvoice\UpdateInvoiceCommand;')
        ->toContain('use Src\Billing\Application\Commands\UpdateInvoice\UpdateInvoiceHandler;')
        ->toContain('use Src\Billing\Application\Commands\DeleteInvoice\DeleteInvoiceCommand;')
        ->toContain('use Src\Billing\Application\Commands\DeleteInvoice\DeleteInvoiceHandler;')
        ->toContain('use Src\Billing\Application\Queries\GetInvoice\GetInvoiceHandler;')
        ->toContain('use Src\Billing\Application\Queries\GetInvoice\GetInvoiceQuery;')
        ->toContain('use Src\Billing\Application\Queries\ListInvoices\ListInvoicesHandler;')
        ->toContain('use Src\Billing\Application\Queries\ListInvoices\ListInvoicesQuery;')
        ->toContain('use Src\Billing\Application\Sanitizers\InvoiceSanitizer;')
        // Constructor
        ->toContain('private readonly CreateInvoiceHandler $createHandler,')
        ->toContain('private readonly UpdateInvoiceHandler $updateHandler,')
        ->toContain('private readonly DeleteInvoiceHandler $deleteHandler,')
        ->toContain('private readonly GetInvoiceHandler $getHandler,')
        ->toContain('private readonly ListInvoicesHandler $listHandler,')
        // index — paginated with request parameters
        ->toContain('$this->listHandler->handle(new ListInvoicesQuery(')
        ->toContain("page: max((int) \$request->query('page', 1), 1)")
        ->toContain("perPage: min(max((int) \$request->query('per_page', 15), 1), 100)")
        ->toContain('InvoiceResource::collection($result->items)')
        ->toContain("->additional(['meta' => \$result->meta()])")
        // show
        ->toContain('$this->getHandler->handle(new GetInvoiceQuery($id))')
        ->toContain('abort_if(! $readModel, 404)')
        // store — id generated at the edge, returned with a Location header
        ->toContain('use Illuminate\Support\Str;')
        ->toContain('$id = (string) Str::uuid7();')
        ->toContain('InvoiceSanitizer::sanitize($request->validated())')
        ->toContain('$this->createHandler->handle(new CreateInvoiceCommand($id, $sanitized))')
        ->toContain("return response()->json(['id' => \$id], 201)")
        ->toContain("->header('Location', \$request->url() . '/' . \$id)")
        // update
        ->toContain('$this->updateHandler->handle(new UpdateInvoiceCommand($id, $sanitized))')
        ->toContain('return response()->noContent();')
        // destroy
        ->toContain('$this->deleteHandler->handle(new DeleteInvoiceCommand($id))');
});
