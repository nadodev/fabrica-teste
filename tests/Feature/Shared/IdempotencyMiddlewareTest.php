<?php

use Illuminate\Support\Facades\Route;

it('replays successful requests and rejects key reuse with another payload', function () {
    Route::post('/_test/idempotency', fn () => response()->json(['operation' => fake()->uuid()]))
        ->middleware('idempotent')
        ->name('test.idempotency');

    $first = $this->postJson('/_test/idempotency', ['order' => 'A'], ['Idempotency-Key' => 'checkout-001'])
        ->assertOk();

    $replay = $this->postJson('/_test/idempotency', ['order' => 'A'], ['Idempotency-Key' => 'checkout-001'])
        ->assertOk()
        ->assertHeader('Idempotent-Replayed', 'true');

    expect($replay->json())->toBe($first->json());

    $this->postJson('/_test/idempotency', ['order' => 'B'], ['Idempotency-Key' => 'checkout-001'])
        ->assertConflict();
});

it('requires a bounded idempotency key', function () {
    Route::post('/_test/idempotency-required', fn () => response()->noContent())
        ->middleware('idempotent')
        ->name('test.idempotency-required');

    $this->postJson('/_test/idempotency-required')->assertUnprocessable();
});
