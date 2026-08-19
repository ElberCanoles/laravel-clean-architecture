<?php

use Illuminate\Support\Facades\File;

/**
 * Stubs published before ULID support hardcode the identifier and lack the
 * {{IdTrait}} / {{idType}} placeholders, so --id-type would be silently dropped.
 */
afterEach(function () {
    File::deleteDirectory(base_path('stubs/clean-architecture'));
});

test('warns when a custom model stub has no IdTrait placeholder', function () {
    publishStub('model', <<<'STUB'
        <?php

        namespace {{Namespace}}\Infrastructure\Models;

        use Illuminate\Database\Eloquent\Concerns\HasUuids;
        use Illuminate\Database\Eloquent\Model;

        class {{Class}}Model extends Model
        {
            use HasUuids;

            protected $table = '{{table}}';
        }
        STUB);

    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice', '--id-type' => 'ulid'])
        ->expectsOutputToContain('has no {{IdTrait}} placeholder')
        ->assertSuccessful();
});

test('does not warn when a custom model stub has the IdTrait placeholder', function () {
    publishStub('model', <<<'STUB'
        <?php

        namespace {{Namespace}}\Infrastructure\Models;

        use Illuminate\Database\Eloquent\Concerns\{{IdTrait}};
        use Illuminate\Database\Eloquent\Model;

        class {{Class}}Model extends Model
        {
            use {{IdTrait}};

            protected $table = '{{table}}';
        }
        STUB);

    $this->artisan('clean:model', ['context' => 'Billing', 'name' => 'Invoice', '--id-type' => 'ulid'])
        ->doesntExpectOutputToContain('placeholder')
        ->assertSuccessful();

    expect(file_get_contents($this->tempDir . '/Billing/Infrastructure/Models/InvoiceModel.php'))
        ->toContain('use HasUlids;');
});

test('warns when a custom migration stub has no idType placeholder', function () {
    publishStub('migration', <<<'STUB'
        <?php

        use Illuminate\Database\Migrations\Migration;
        use Illuminate\Database\Schema\Blueprint;
        use Illuminate\Support\Facades\Schema;

        return new class extends Migration
        {
            public function up(): void
            {
                Schema::create('{{table}}', function (Blueprint $table) {
                    $table->uuid('id')->primary();
                    $table->timestamps();
                });
            }

            public function down(): void
            {
                Schema::dropIfExists('{{table}}');
            }
        };
        STUB);

    $this->artisan('clean:scaffold', ['context' => 'Billing', 'name' => 'Invoice', '--id-type' => 'ulid'])
        ->expectsOutputToContain('has no {{idType}} placeholder')
        ->assertSuccessful();
});
