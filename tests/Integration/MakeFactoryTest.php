<?php

declare(strict_types=1);

use Src\Catalog\Infrastructure\Database\Factories\GizmoModelFactory;
use Src\Catalog\Infrastructure\Models\GizmoModel;

test('wires HasFactory and #[UseFactory] into the generated model', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    $this->artisan('clean:factory', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('Model wired')
        ->assertSuccessful();

    $model = $this->tempDir . '/Billing/Infrastructure/Models/InvoiceModel.php';
    expect($model)->toBeValidPhp();

    $content = (string) file_get_contents($model);
    expect($content)
        ->toContain("#[UseFactory(InvoiceModelFactory::class)]\nclass InvoiceModel extends Model\n{\n    use HasFactory;\n    use HasUuids;\n")
        ->toContain('use Illuminate\Database\Eloquent\Attributes\UseFactory;')
        ->toContain('use Illuminate\Database\Eloquent\Factories\HasFactory;')
        ->toContain('use Src\Billing\Infrastructure\Database\Factories\InvoiceModelFactory;');

    // The import block stays sorted, as Pint would leave it.
    $imports = array_values(array_filter(explode("\n", $content), fn (string $line) => str_starts_with($line, 'use ')));
    $sorted = $imports;
    sort($sorted, SORT_STRING | SORT_FLAG_CASE);
    expect($imports)->toBe($sorted);
});

test('the wired model resolves its factory at runtime', function () {
    $this->artisan('clean:model', ['context' => 'Catalog', 'name' => 'Gizmo'])->assertSuccessful();
    $this->artisan('clean:factory', ['context' => 'Catalog', 'name' => 'Gizmo'])->assertSuccessful();

    require_once $this->tempDir . '/Catalog/Infrastructure/Models/GizmoModel.php';
    require_once $this->tempDir . '/Catalog/Infrastructure/Database/Factories/GizmoModelFactory.php';

    $factory = GizmoModel::factory();

    expect($factory)->toBeInstanceOf(GizmoModelFactory::class)
        ->and($factory->make())->toBeInstanceOf(GizmoModel::class);
});

test('wiring is idempotent', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();
    $this->artisan('clean:factory', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();
    $this->artisan('clean:factory', ['context' => 'Billing', 'name' => 'Invoice', '--force' => true])->assertSuccessful();

    $content = (string) file_get_contents($this->tempDir . '/Billing/Infrastructure/Models/InvoiceModel.php');

    expect(substr_count($content, '#[UseFactory('))->toBe(1)
        ->and(substr_count($content, 'use HasFactory;'))->toBe(1)
        ->and(substr_count($content, 'use Illuminate\Database\Eloquent\Factories\HasFactory;'))->toBe(1);
});

test('warns when the model does not exist', function () {
    $this->artisan('clean:factory', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('Model InvoiceModel not found')
        ->assertSuccessful();

    expect(file_exists($this->tempDir . '/Billing/Infrastructure/Database/Factories/InvoiceModelFactory.php'))->toBeTrue();
});

test('wires a hand-written model that has no imports', function () {
    $dir = $this->tempDir . '/Billing/Infrastructure/Models';
    mkdir($dir, 0755, true);
    file_put_contents("$dir/InvoiceModel.php", <<<'PHP'
<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Models;

final class InvoiceModel extends \Illuminate\Database\Eloquent\Model
{
    protected $table = 'invoices';
}

PHP);

    $this->artisan('clean:factory', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('Model wired')
        ->assertSuccessful();

    $model = "$dir/InvoiceModel.php";
    expect($model)->toBeValidPhp();
    expect((string) file_get_contents($model))
        ->toContain("namespace Src\Billing\Infrastructure\Models;\n\nuse Illuminate\Database\Eloquent\Attributes\UseFactory;\nuse Illuminate\Database\Eloquent\Factories\HasFactory;\nuse Src\Billing\Infrastructure\Database\Factories\InvoiceModelFactory;\n")
        ->toContain("#[UseFactory(InvoiceModelFactory::class)]\nfinal class InvoiceModel extends \Illuminate\Database\Eloquent\Model\n{\n    use HasFactory;\n\n    protected \$table = 'invoices';\n");
});

test('does not add the trait twice when the model already uses HasFactory', function () {
    $dir = $this->tempDir . '/Billing/Infrastructure/Models';
    mkdir($dir, 0755, true);
    file_put_contents("$dir/InvoiceModel.php", <<<'PHP'
<?php

declare(strict_types=1);

namespace Src\Billing\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceModel extends Model
{
    use HasFactory;
}

PHP);

    $this->artisan('clean:factory', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    $content = (string) file_get_contents("$dir/InvoiceModel.php");
    expect("$dir/InvoiceModel.php")->toBeValidPhp();
    expect(substr_count($content, 'use HasFactory;'))->toBe(1)
        ->and(substr_count($content, 'use Illuminate\Database\Eloquent\Factories\HasFactory;'))->toBe(1)
        ->and($content)->toContain('#[UseFactory(InvoiceModelFactory::class)]');
});

test('leaves a model it cannot parse untouched', function () {
    $dir = $this->tempDir . '/Billing/Infrastructure/Models';
    mkdir($dir, 0755, true);
    $original = "<?php\n\n// Not a class declaration this generator understands.\n\$model = new class {};\n";
    file_put_contents("$dir/InvoiceModel.php", $original);

    $this->artisan('clean:factory', ['context' => 'Billing', 'name' => 'Invoice'])
        ->expectsOutputToContain('Could not wire InvoiceModelFactory into InvoiceModel')
        ->assertSuccessful();

    expect(file_get_contents("$dir/InvoiceModel.php"))->toBe($original);
});

test('wires models saved with Windows line endings', function () {
    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    $model = $this->tempDir . '/Billing/Infrastructure/Models/InvoiceModel.php';
    file_put_contents($model, str_replace("\n", "\r\n", (string) file_get_contents($model)));

    $this->artisan('clean:factory', ['context' => 'Billing', 'name' => 'Invoice'])->assertSuccessful();

    $content = (string) file_get_contents($model);
    expect($model)->toBeValidPhp();
    expect($content)
        ->toContain("#[UseFactory(InvoiceModelFactory::class)]\r\nclass InvoiceModel extends Model\r\n{\r\n    use HasFactory;\r\n    use HasUuids;\r\n")
        ->not->toContain("\n\n\n");
});
