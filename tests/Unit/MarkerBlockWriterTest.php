<?php

declare(strict_types=1);

use CleanArchitecture\Kernel\MarkerBlockWriter;

test('replaces the placeholder comments with the insertion', function () {
    $content = "class P\n{\n    public function register(): void\n    {\n        // {bindings}\n        // TODO: bind\n        // \$this->app->bind(A::class, B::class);\n\n        // {/bindings}\n    }\n}\n";

    $once = MarkerBlockWriter::insert($content, 'bindings', '        $this->app->bind(A::class, B::class);');

    expect($once)->toBe("class P\n{\n    public function register(): void\n    {\n        // {bindings}\n        \$this->app->bind(A::class, B::class);\n        // {/bindings}\n    }\n}\n");
});

test('keeps previously inserted code, in order, before the new insertion', function () {
    $content = "// {routes}\n    Route::apiResource('invoices', InvoiceController::class);\n    // {/routes}\n";

    $twice = MarkerBlockWriter::insert($content, 'routes', "    Route::apiResource('payments', PaymentController::class);");

    expect($twice)->toBe("// {routes}\n    Route::apiResource('invoices', InvoiceController::class);\n    Route::apiResource('payments', PaymentController::class);\n    // {/routes}\n");
});

test('drops blank lines and comments inside the block but nothing outside it', function () {
    $content = "// before\n// {m}\n\n// gone\n   \n// {/m}\n// after\n";

    expect(MarkerBlockWriter::insert($content, 'm', 'kept();'))
        ->toBe("// before\n// {m}\nkept();\n// {/m}\n// after\n");
});

test('leaves content without markers untouched', function () {
    expect(MarkerBlockWriter::insert("no markers here\n", 'bindings', 'x'))->toBe("no markers here\n");
});
