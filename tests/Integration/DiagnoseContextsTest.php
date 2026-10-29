<?php

use CleanArchitecture\Kernel\ModuleLoader;
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

test('prints the configuration it checks', function () {
    File::ensureDirectoryExists($this->tempDir . '/Billing/Infrastructure');
    File::put($this->tempDir . '/Billing/Infrastructure/BillingServiceProvider.php', "<?php\n// {bindings}\n// {/bindings}\n");

    $output = artisanOutput('clean:doctor');

    // Paths print relative to the project root; nothing may follow the value.
    expect($output)->toMatch('/Contexts path.*' . preg_quote($this->relativeTempDir, '/') . '\s*$/m')
        ->not->toContain('(missing)')
        ->toMatch('/Id type[ .]+uuid\s*$/m')
        ->not->toContain('invalid')
        ->toMatch('/Context cache[ .]+not cached\s*$/m')
        ->toMatch('/Context \[Billing\][ .]+ok\s*$/m')
        ->toContain('1 context(s) checked, no issues found.');
});

test('flags a missing contexts directory', function () {
    config()->set('clean-architecture.contexts_path', $this->relativeTempDir . '/nowhere');

    $output = artisanOutput('clean:doctor');

    expect($output)->toMatch('/Contexts path.*nowhere \(missing\)\s*$/m')
        ->toContain('none discovered')
        ->not->toContain('context(s) checked');
});

test('accepts ulid as id type and spells out an invalid one', function () {
    config()->set('clean-architecture.id_type', 'ulid');

    expect(artisanOutput('clean:doctor'))
        ->toMatch('/Id type[ .]+ulid\s*$/m')
        ->not->toContain('invalid');

    config()->set('clean-architecture.id_type', 'snowflake');

    // Reported even before the first context exists.
    expect(artisanOutput('clean:doctor'))
        ->toMatch('/Id type[ .]+snowflake \(invalid — must be uuid or ulid\)\s*$/m')
        ->toContain('Some checks reported issues');

    // Without the console mock, artisan() hands back the exit code itself.
    expect($this->artisan('clean:doctor'))->toBe(1);
});

test('reports the cache state', function () {
    $this->artisan('clean:context', ['name' => 'Billing'])->assertSuccessful();
    $this->artisan('clean:cache')->assertSuccessful();

    expect(artisanOutput('clean:doctor'))
        ->toMatch('/Context cache[ .]+cached \(clean:clear to refresh\)\s*$/m')
        ->not->toContain('not cached');
});

test('rescans instead of trusting the memoized discovery', function () {
    // Populate the per-request memo before the context exists.
    expect(ModuleLoader::contextNames())->toBe([]);

    $this->artisan('clean:context', ['name' => 'Billing'])->assertSuccessful();

    $this->artisan('clean:doctor')
        ->expectsOutputToContain('Context [Billing]')
        ->doesntExpectOutputToContain('none discovered')
        ->assertSuccessful();
});

test('notes a context without a ServiceProvider instead of calling it ok', function () {
    mkdir($this->tempDir . '/Billing/Domain', 0755, true);

    expect(artisanOutput('clean:doctor'))
        ->toMatch('/Context \[Billing\][ .]+no ServiceProvider \(auto-discovery skips it\)\s*$/m')
        ->not->toMatch('/[ .]ok\s*$/m')
        ->toContain('no issues found');
});
