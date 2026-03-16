<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Contexts Path
    |--------------------------------------------------------------------------
    |
    | The directory where bounded contexts are stored, relative to base_path().
    |
    */
    'contexts_path' => 'src',

    /*
    |--------------------------------------------------------------------------
    | Namespace Prefix
    |--------------------------------------------------------------------------
    |
    | The root namespace prefix for all bounded contexts.
    | e.g. App\Billing, App\Inventory, etc.
    |
    */
    'namespace_prefix' => 'App',

    /*
    |--------------------------------------------------------------------------
    | Auto-discover Contexts
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will automatically discover and register
    | service providers found in each context's Infrastructure directory.
    |
    */
    'auto_discover' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto-load Contexts
    |--------------------------------------------------------------------------
    |
    | When enabled, the package will automatically register PSR-4 autoloading
    | for all bounded contexts in the contexts_path directory.
    |
    */
    'auto_load' => true,

    /*
    |--------------------------------------------------------------------------
    | Architecture Tests Path
    |--------------------------------------------------------------------------
    |
    | The directory where architecture tests are generated, relative to base_path().
    |
    */
    'arch_tests_path' => 'tests/Feature/Architecture',

    /*
    |--------------------------------------------------------------------------
    | Unit Tests Path
    |--------------------------------------------------------------------------
    |
    | The directory where domain unit tests are generated, relative to base_path().
    |
    */
    'unit_tests_path' => 'tests/Unit/Domain',
];
