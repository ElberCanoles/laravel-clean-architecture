<?php

declare(strict_types=1);

use CleanArchitecture\Support\ProvidesHttpStatus;
use Illuminate\Support\Facades\Route;

function throwPlainDomainException(): never
{
    throw new DomainException('Invoice is already paid.');
}

function throwNotFoundStyleException(): never
{
    throw new class('Invoice with id [abc] was not found.') extends DomainException implements ProvidesHttpStatus
    {
        public function httpStatus(): int
        {
            return 404;
        }
    };
}

test('plain domain exceptions render as 422 JSON on API requests', function () {
    Route::get('/boom', fn () => throwPlainDomainException());

    $this->getJson('/boom')
        ->assertStatus(422)
        ->assertJson(['message' => 'Invoice is already paid.']);
});

test('exceptions providing an HTTP status choose their own code', function () {
    Route::get('/missing', fn () => throwNotFoundStyleException());

    $this->getJson('/missing')
        ->assertStatus(404)
        ->assertJson(['message' => 'Invoice with id [abc] was not found.']);
});

test('non-JSON requests fall through to default handling', function () {
    Route::get('/boom-html', fn () => throwPlainDomainException());

    $this->get('/boom-html')->assertStatus(500);
});

test('the renderer can be disabled via config', function () {
    config()->set('clean-architecture.render_domain_exceptions', false);

    Route::get('/boom-off', fn () => throwPlainDomainException());

    $this->getJson('/boom-off')->assertStatus(500);
});
