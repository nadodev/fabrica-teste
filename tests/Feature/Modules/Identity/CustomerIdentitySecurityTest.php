<?php

use App\Models\User;
use App\Modules\Identity\Application\Port\CustomerNotificationSender;
use App\Modules\Identity\Infrastructure\Notification\CustomerResetPasswordNotification;
use App\Modules\Identity\Infrastructure\Notification\CustomerVerifyEmailNotification;
use Illuminate\Auth\Events\PasswordReset as PasswordResetEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

it('registers a customer as unverified and sends a verification link', function () {
    Notification::fake();

    $this->post(route('cliente.register.store'), [
        'name' => '  Cliente Teste  ',
        'email' => 'CLIENTE@EXAMPLE.COM',
        'password' => 'SenhaSegura123',
        'password_confirmation' => 'SenhaSegura123',
        'is_admin' => true,
    ])->assertRedirect(route('verification.notice'));

    $customer = User::query()->where('email', 'cliente@example.com')->firstOrFail();
    expect($customer->name)->toBe('Cliente Teste')
        ->and($customer->hasVerifiedEmail())->toBeFalse()
        ->and($customer->is_admin)->toBeFalse();
    $this->assertAuthenticatedAs($customer);
    Notification::assertSentTo($customer, CustomerVerifyEmailNotification::class);
});

it('preserves a newly created account when the first verification delivery fails', function () {
    $this->app->instance(CustomerNotificationSender::class, new class implements CustomerNotificationSender
    {
        public function sendEmailVerification(int $customerId): bool
        {
            return false;
        }
    });

    $this->post(route('cliente.register.store'), [
        'name' => 'Cliente Teste',
        'email' => 'cliente@example.com',
        'password' => 'SenhaSegura123',
        'password_confirmation' => 'SenhaSegura123',
    ])->assertRedirect(route('verification.notice'));

    $customer = User::query()->where('email', 'cliente@example.com')->firstOrFail();
    expect($customer->hasVerifiedEmail())->toBeFalse();
    $this->assertAuthenticatedAs($customer);
});

it('rejects weak passwords consistently on registration', function () {
    $this->post(route('cliente.register.store'), [
        'name' => 'Cliente Teste',
        'email' => 'cliente@example.com',
        'password' => 'senhafraca',
        'password_confirmation' => 'senhafraca',
    ])->assertSessionHasErrors('password');

    $this->assertDatabaseMissing('users', ['email' => 'cliente@example.com']);
});

it('requires a verified email before exposing the customer account', function () {
    $customer = User::factory()->unverified()->create();

    $this->actingAs($customer)
        ->get(route('cliente.conta'))
        ->assertRedirect(route('verification.notice'));

    $this->actingAs($customer)
        ->get(route('verification.notice'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('cliente/verificar-email')
            ->where('email', $customer->email));
});

it('verifies the authenticated customer through a signed expiring link', function () {
    $customer = User::factory()->unverified()->create();
    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $customer->id,
        'hash' => sha1($customer->email),
    ]);

    $this->actingAs($customer)->get($url)
        ->assertRedirect(route('cliente.conta'))
        ->assertSessionHas('success');

    expect($customer->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects an expired or mismatched verification link', function () {
    $customer = User::factory()->unverified()->create();
    $other = User::factory()->unverified()->create();
    $mismatched = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $other->id,
        'hash' => sha1($other->email),
    ]);

    $this->actingAs($customer)->get($mismatched)->assertForbidden();

    $expired = URL::temporarySignedRoute('verification.verify', now()->subMinute(), [
        'id' => $customer->id,
        'hash' => sha1($customer->email),
    ]);
    $this->actingAs($customer)->get($expired)->assertForbidden();
    expect($customer->fresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends verification without allowing an authenticated user to verify another account', function () {
    Notification::fake();
    $customer = User::factory()->unverified()->create();

    $this->actingAs($customer)->post(route('verification.send'))
        ->assertRedirect()
        ->assertSessionHas('success');

    Notification::assertSentTo($customer, CustomerVerifyEmailNotification::class);
});

it('sends password reset instructions without revealing whether the account exists', function () {
    Notification::fake();
    $customer = User::factory()->create(['email' => 'cliente@example.com']);

    $existing = $this->post(route('password.email'), ['email' => $customer->email]);
    $unknown = $this->post(route('password.email'), ['email' => 'desconhecido@example.com']);

    $existing->assertRedirect()->assertSessionHas('success');
    $unknown->assertRedirect()->assertSessionHas('success');
    expect($existing->getSession()->get('success'))->toBe($unknown->getSession()->get('success'));
    Notification::assertSentTo($customer, CustomerResetPasswordNotification::class);
});

it('resets the password with a valid single-use token', function () {
    Event::fake([PasswordResetEvent::class]);
    $customer = User::factory()->create();
    $token = Password::broker()->createToken($customer);

    $this->post(route('password.update'), [
        'email' => $customer->email,
        'token' => $token,
        'password' => 'NovaSenhaSegura123',
        'password_confirmation' => 'NovaSenhaSegura123',
    ])->assertRedirect(route('cliente.login'))->assertSessionHas('success');

    expect(Hash::check('NovaSenhaSegura123', $customer->fresh()->password))->toBeTrue();
    Event::assertDispatched(PasswordResetEvent::class, fn (PasswordResetEvent $event): bool => $event->user->is($customer));

    $this->post(route('password.update'), [
        'email' => $customer->email,
        'token' => $token,
        'password' => 'OutraSenhaSegura123',
        'password_confirmation' => 'OutraSenhaSegura123',
    ])->assertSessionHasErrors('email');
});

it('rejects invalid and expired password reset tokens', function () {
    $customer = User::factory()->create();

    $this->post(route('password.update'), [
        'email' => $customer->email,
        'token' => 'token-invalido',
        'password' => 'NovaSenhaSegura123',
        'password_confirmation' => 'NovaSenhaSegura123',
    ])->assertSessionHasErrors('email');

    $token = Password::broker()->createToken($customer);
    $this->travel(61)->minutes();
    $this->post(route('password.update'), [
        'email' => $customer->email,
        'token' => $token,
        'password' => 'NovaSenhaSegura123',
        'password_confirmation' => 'NovaSenhaSegura123',
    ])->assertSessionHasErrors('email');

    expect(Hash::check('NovaSenhaSegura123', $customer->fresh()->password))->toBeFalse();
});

it('rate limits password recovery requests', function () {
    Notification::fake();
    User::factory()->create(['email' => 'cliente@example.com']);

    foreach (range(1, 5) as $attempt) {
        $this->post(route('password.email'), ['email' => 'cliente@example.com'])
            ->assertRedirect();
    }

    $this->post(route('password.email'), ['email' => 'cliente@example.com'])
        ->assertTooManyRequests();
});
