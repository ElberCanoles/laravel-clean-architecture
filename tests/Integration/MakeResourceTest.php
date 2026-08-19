<?php

test('creates api resource in presentation layer', function () {
    $this->artisan('clean:resource', ['context' => 'Billing', 'name' => 'Invoice'])
        ->assertSuccessful();

    $file = $this->tempDir . '/Billing/Presentation/Resources/InvoiceResource.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain('namespace Src\Billing\Presentation\Resources;')
        ->toContain('class InvoiceResource extends JsonResource')
        ->toContain('public function toArray(Request $request): array')
        ->toContain("'id' => \$this->id");
});
