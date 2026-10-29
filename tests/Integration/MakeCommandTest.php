<?php

test('creates command and handler files', function () {
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice'])
        ->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Commands/PayInvoice';

    $commandFile = "$base/PayInvoiceCommand.php";
    $handlerFile = "$base/PayInvoiceHandler.php";

    expect(file_exists($commandFile))->toBeTrue();
    expect(file_exists($handlerFile))->toBeTrue();

    $commandContent = file_get_contents($commandFile);
    expect($commandContent)
        ->toContain('namespace Src\Billing\Application\Commands\PayInvoice;')
        ->toContain('readonly class PayInvoiceCommand')
        ->toContain('public string $id,');

    $handlerContent = file_get_contents($handlerFile);
    expect($handlerContent)
        ->toContain('namespace Src\Billing\Application\Commands\PayInvoice;')
        ->toContain('class PayInvoiceHandler')
        ->toContain('public function handle(PayInvoiceCommand $command): void')
        ->toContain('// TODO: Inject your WriteRepository');
});

test('creates handler with entity injection when --entity is provided', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'PayInvoice',
        '--entity' => 'Invoice',
    ])->assertSuccessful();

    $handlerFile = $this->tempDir . '/Billing/Application/Commands/PayInvoice/PayInvoiceHandler.php';
    $handlerContent = file_get_contents($handlerFile);

    expect($handlerContent)
        ->toContain('use Src\Billing\Domain\Repositories\InvoiceWriteRepository;')
        ->toContain('private readonly InvoiceWriteRepository $repository,')
        ->not->toContain('use Src\Billing\Domain\Entities\Invoice;');
});

test('warns when command files exist without --force', function () {
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice']);

    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice'])
        ->expectsOutputToContain('File already exists');
});

test('creates create command with --crud=create', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'CreateInvoice',
        '--entity' => 'Invoice',
        '--crud' => 'create',
    ])->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Commands/CreateInvoice';

    $commandContent = file_get_contents("$base/CreateInvoiceCommand.php");
    expect($commandContent)
        ->toContain('public string $id,')
        ->toContain('public array $data,');

    $handlerContent = file_get_contents("$base/CreateInvoiceHandler.php");
    expect($handlerContent)
        ->toContain('use Src\Billing\Domain\Entities\Invoice;')
        ->toContain('Invoice::create($command->id)')
        ->toContain('$this->repository->save($entity);')
        // The id travels in the command — the Application layer stays framework-free
        ->not->toContain('Illuminate');
});

test('creates update command with --crud=update', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'UpdateInvoice',
        '--entity' => 'Invoice',
        '--crud' => 'update',
    ])->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Commands/UpdateInvoice';

    $commandContent = file_get_contents("$base/UpdateInvoiceCommand.php");
    expect($commandContent)
        ->toContain('public string $id,')
        ->toContain('public array $data,');

    $handlerContent = file_get_contents("$base/UpdateInvoiceHandler.php");
    expect($handlerContent)
        ->toContain('use Src\Billing\Domain\Exceptions\InvoiceNotFound;')
        ->toContain('use Src\Billing\Domain\Repositories\InvoiceWriteRepository;')
        // Real load-guard-save flow instead of a silent no-op
        ->toContain('$entity = $this->repository->ofId($command->id);')
        ->toContain('throw InvoiceNotFound::withId($command->id);')
        ->toContain('$this->repository->save($entity);')
        ->not->toContain('use Src\Billing\Domain\Entities\Invoice;');

    expect(file_exists($this->tempDir . '/Billing/Domain/Exceptions/InvoiceNotFound.php'))->toBeTrue();
});

test('creates delete command with --crud=delete', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'DeleteInvoice',
        '--entity' => 'Invoice',
        '--crud' => 'delete',
    ])->assertSuccessful();

    $base = $this->tempDir . '/Billing/Application/Commands/DeleteInvoice';

    $commandContent = file_get_contents("$base/DeleteInvoiceCommand.php");
    expect($commandContent)
        ->toContain('public string $id,')
        ->not->toContain('public array $data,');

    $handlerContent = file_get_contents("$base/DeleteInvoiceHandler.php");
    expect($handlerContent)
        ->toContain('use Src\Billing\Domain\Repositories\InvoiceWriteRepository;')
        ->not->toContain('use Src\Billing\Domain\Entities\Invoice;')
        // Guards against deleting a missing aggregate instead of silently succeeding
        ->toContain('if (! $this->repository->ofId($command->id))')
        ->toContain('throw InvoiceNotFound::withId($command->id);')
        ->toContain('$this->repository->delete($command->id);');
});

test('generated handlers never import the framework regardless of id type', function (string $idType) {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'CreateInvoice',
        '--entity' => 'Invoice',
        '--crud' => 'create',
        '--id-type' => $idType,
        '--force' => true,
    ])->assertSuccessful();

    $handlerContent = file_get_contents(
        $this->tempDir . '/Billing/Application/Commands/CreateInvoice/CreateInvoiceHandler.php'
    );

    expect($handlerContent)->not->toContain('Illuminate');
})->with(['uuid', 'ulid']);

test('update and delete do not duplicate the NotFound exception', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'UpdateInvoice',
        '--entity' => 'Invoice',
        '--crud' => 'update',
    ])->assertSuccessful();

    $exceptionFile = $this->tempDir . '/Billing/Domain/Exceptions/InvoiceNotFound.php';
    expect(file_exists($exceptionFile))->toBeTrue();
    $original = file_get_contents($exceptionFile);

    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'DeleteInvoice',
        '--entity' => 'Invoice',
        '--crud' => 'delete',
    ])->assertSuccessful();

    expect(file_get_contents($exceptionFile))->toBe($original);

    $content = file_get_contents($exceptionFile);
    expect($content)
        ->toContain('final class InvoiceNotFound extends \DomainException implements ProvidesHttpStatus')
        ->toContain('public function httpStatus(): int')
        ->toContain('return 404;');
});

test('rejects invalid --id-type value on command', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'CreateInvoice',
        '--entity' => 'Invoice',
        '--crud' => 'create',
        '--id-type' => 'snowflake',
    ])
        ->expectsOutputToContain("Invalid id type: 'snowflake'")
        ->assertExitCode(2);
});

test('rejects invalid --id-type even when no entity is injected', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'PayInvoice',
        '--id-type' => 'snowflake',
    ])
        ->expectsOutputToContain("Invalid id type: 'snowflake'")
        ->assertExitCode(2);
});

test('rejects invalid --crud value', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'PayInvoice',
        '--crud' => 'invalid',
    ])
        ->expectsOutputToContain('Invalid --crud value')
        ->assertExitCode(2);
});

test('normalizes the entity option to StudlyCase', function () {
    $this->artisan('clean:command', [
        'context' => 'Billing',
        'name' => 'PayInvoice',
        '--entity' => 'gift-card',
    ])->assertSuccessful();

    $handlerContent = file_get_contents($this->tempDir . '/Billing/Application/Commands/PayInvoice/PayInvoiceHandler.php');
    expect($handlerContent)->toContain('GiftCardWriteRepository');
});

test('crud handlers render their bodies exactly', function () {
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'CreateInvoice', '--entity' => 'Invoice', '--crud' => 'create'])
        ->expectsOutputToContain('Command created')
        ->assertSuccessful();
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'UpdateInvoice', '--entity' => 'Invoice', '--crud' => 'update'])
        ->expectsOutputToContain('Domain exception created')
        ->assertSuccessful();

    $create = <<<'PHP'
    public function handle(CreateInvoiceCommand $command): void
    {
        $entity = Invoice::create($command->id);

        // TODO: Apply $command->data to the entity before saving
        $this->repository->save($entity);
    }
PHP;

    $update = <<<'PHP'
    public function handle(UpdateInvoiceCommand $command): void
    {
        $entity = $this->repository->ofId($command->id);

        if (! $entity) {
            throw InvoiceNotFound::withId($command->id);
        }

        // TODO: Apply changes from $command->data to the entity
        $this->repository->save($entity);
    }
PHP;

    expect(file_get_contents($this->tempDir . '/Billing/Application/Commands/CreateInvoice/CreateInvoiceHandler.php'))->toContain($create);
    expect(file_get_contents($this->tempDir . '/Billing/Application/Commands/UpdateInvoice/UpdateInvoiceHandler.php'))->toContain($update);
});

test('generates the missing half when only one command file exists', function () {
    $base = $this->tempDir . '/Billing/Application/Commands/PayInvoice';
    mkdir($base, 0755, true);
    file_put_contents("$base/PayInvoiceCommand.php", '<?php // mine');

    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice'])
        ->expectsOutputToContain('File already exists')
        ->assertSuccessful();

    expect(file_get_contents("$base/PayInvoiceCommand.php"))->toBe('<?php // mine')
        ->and(file_exists("$base/PayInvoiceHandler.php"))->toBeTrue();
});

test('fails when both command files exist without --force', function () {
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice'])->assertSuccessful();

    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice'])
        ->doesntExpectOutputToContain('Command created')
        ->assertExitCode(1);
});

test('a customized NotFound exception is never regenerated', function () {
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'UpdateInvoice', '--entity' => 'Invoice', '--crud' => 'update'])
        ->assertSuccessful();

    $exception = $this->tempDir . '/Billing/Domain/Exceptions/InvoiceNotFound.php';
    file_put_contents($exception, '<?php // customized by hand');

    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'DeleteInvoice', '--entity' => 'Invoice', '--crud' => 'delete'])
        ->doesntExpectOutputToContain('Domain exception created')
        ->assertSuccessful();

    expect(file_get_contents($exception))->toBe('<?php // customized by hand');
});

test('the delete command also provides the NotFound exception', function () {
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'DeleteInvoice', '--entity' => 'Invoice', '--crud' => 'delete'])
        ->expectsOutputToContain('Domain exception created')
        ->assertSuccessful();

    expect(file_exists($this->tempDir . '/Billing/Domain/Exceptions/InvoiceNotFound.php'))->toBeTrue();
});

test('a handler without --entity is valid PHP', function () {
    $this->artisan('clean:command', ['context' => 'Billing', 'name' => 'PayInvoice'])->assertSuccessful();

    expect($this->tempDir . '/Billing/Application/Commands/PayInvoice/PayInvoiceHandler.php')->toBeValidPhp();
    expect($this->tempDir . '/Billing/Application/Commands/PayInvoice/PayInvoiceCommand.php')->toBeValidPhp();
});
