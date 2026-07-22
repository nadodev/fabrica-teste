<?php

use App\Modules\Administration\Domain\AdminChallengeStatus;
use App\Modules\Administration\Domain\AdminLoginChallenge;

it('consumes a valid administrative challenge once', function () {
    $now = new DateTimeImmutable('2026-07-22 12:00:00');
    $challenge = new AdminLoginChallenge(
        'challenge-id',
        10,
        'expected-hash',
        false,
        $now->modify('+10 minutes'),
        0,
        5,
    );

    expect($challenge->verify('expected-hash', $now))->toBe(AdminChallengeStatus::Success)
        ->and($challenge->verify('expected-hash', $now))->toBe(AdminChallengeStatus::Consumed)
        ->and($challenge->consumedAt)->toEqual($now);
});

it('locks a challenge at the configured attempt limit', function () {
    $now = new DateTimeImmutable('2026-07-22 12:00:00');
    $challenge = new AdminLoginChallenge(
        'challenge-id',
        10,
        'expected-hash',
        false,
        $now->modify('+10 minutes'),
        0,
        2,
    );

    expect($challenge->verify('wrong-hash', $now))->toBe(AdminChallengeStatus::Invalid)
        ->and($challenge->attempts)->toBe(1)
        ->and($challenge->verify('wrong-hash', $now))->toBe(AdminChallengeStatus::Locked)
        ->and($challenge->consumedAt)->toEqual($now);
});

it('expires a challenge without accepting its code', function () {
    $expiresAt = new DateTimeImmutable('2026-07-22 12:00:00');
    $challenge = new AdminLoginChallenge(
        'challenge-id',
        10,
        'expected-hash',
        false,
        $expiresAt,
        0,
        5,
    );

    expect($challenge->verify('expected-hash', $expiresAt))->toBe(AdminChallengeStatus::Expired)
        ->and($challenge->consumedAt)->toEqual($expiresAt);
});
