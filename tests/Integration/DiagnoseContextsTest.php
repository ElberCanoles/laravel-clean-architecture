<?php

use Illuminate\Support\Facades\File;

test('reports healthy contexts', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])->assertSuccessful();

    $this->artisan('clean:doctor')
        ->expectsOutputToContain('Context [Billing]')
        ->expectsOutputToContain('no issues found')
        ->assertSuccessful();
});

test('flags a ServiceProvider without wiring markers', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])->assertSuccessful();

    $spFile = $this->tempDir . '/Billing/Infrastructure/BillingServiceProvider.php';
    File::put($spFile, str_replace('// {bindings}', '', (string) File::get($spFile)));

    $this->artisan('clean:doctor')
        ->expectsOutputToContain('no {bindings} markers')
        ->assertExitCode(1);
});

test('flags an invalid id_type', function () {
    config()->set('clean-architecture.id_type', 'snowflake');
    $this->artisan('clean:context', ['name' => 'Billing'])->assertSuccessful();

    $this->artisan('clean:doctor')
        ->expectsOutputToContain('invalid')
        ->assertExitCode(1);
});

test('handles an empty contexts directory', function () {
    $this->artisan('clean:doctor')
        ->expectsOutputToContain('none discovered')
        ->assertSuccessful();
});
