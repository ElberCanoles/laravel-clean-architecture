<?php

test('creates architecture test file with correct content', function () {
    $this->artisan('clean:arch-test', ['context' => 'Billing'])
        ->assertSuccessful();

    $file = $this->tempDir . '/tests/Feature/Architecture/BillingArchTest.php';
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)
        ->toContain("expect('App\Billing\Domain')")
        ->toContain('Billing domain does not depend on infrastructure')
        ->toContain('Billing domain does not depend on application layer')
        ->toContain('Billing application does not depend on presentation')
        ->toContain('Billing application does not depend on infrastructure')
        ->toContain('Billing entities are final classes')
        ->toContain('Billing repositories in domain are interfaces')
        ->toContain('Billing value objects are readonly');
});

test('uses configured namespace prefix in arch tests', function () {
    config(['clean-architecture.namespace_prefix' => 'Domain']);

    $this->artisan('clean:arch-test', ['context' => 'Sales'])
        ->assertSuccessful();

    $file = $this->tempDir . '/tests/Feature/Architecture/SalesArchTest.php';
    $content = file_get_contents($file);

    expect($content)->toContain("expect('Domain\Sales\Domain')");
});

test('warns when arch test file exists without --force', function () {
    $this->artisan('clean:arch-test', ['context' => 'Billing']);

    $this->artisan('clean:arch-test', ['context' => 'Billing'])
        ->expectsOutputToContain('File already exists');
});
