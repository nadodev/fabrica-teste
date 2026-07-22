<?php

use App\Models\User;
use App\Modules\Administration\Application\Command\PruneAdminLoginChallenges;
use App\Modules\Administration\Application\Port\AdminTwoFactorNotifier;
use App\Modules\Administration\Infrastructure\Notification\AdminTwoFactorCodeNotification;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    Notification::fake();
});

it('creates a hashed challenge and keeps the administrator unauthenticated after password validation', function () {
    $administrator = User::factory()->admin()->create([
        'email' => 'owner@example.com',
        'password' => 'safe-password',
    ]);

    $response = $this->post(route('admin.login.store'), [
        'email' => 'owner@example.com',
        'password' => 'safe-password',
        'remember' => true,
    ]);

    $response->assertRedirect(route('admin.two-factor'))->assertSessionHas('admin_login_challenge_id');
    $this->assertGuest();
    $challengeId = session('admin_login_challenge_id');
    expect($challengeId)->toBeString();

    Notification::assertSentTo($administrator, AdminTwoFactorCodeNotification::class, function (AdminTwoFactorCodeNotification $notification) use ($challengeId): bool {
        expect($notification->code)->toMatch('/^\d{6}$/');
        $record = DB::table('admin_login_challenges')->where('id', $challengeId)->first();

        expect($record)->not->toBeNull()
            ->and($record->code_hash)->not->toBe($notification->code)
            ->and((bool) $record->remember)->toBeTrue()
            ->and((int) $record->attempts)->toBe(0);

        return true;
    });

    $this->assertDatabaseHas('admin_audit_logs', [
        'actor_user_id' => $administrator->id,
        'action' => 'admin.login.challenge.start',
        'outcome' => 'completed',
    ]);
});

it('completes the login with the emailed code and rejects replay', function () {
    $administrator = User::factory()->admin()->create([
        'email' => 'owner@example.com',
        'password' => 'safe-password',
    ]);
    $code = null;

    $this->post(route('admin.login.store'), [
        'email' => $administrator->email,
        'password' => 'safe-password',
        'remember' => true,
    ]);
    $challengeId = (string) session('admin_login_challenge_id');
    Notification::assertSentTo($administrator, AdminTwoFactorCodeNotification::class, function (AdminTwoFactorCodeNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    expect($code)->toBeString();
    $response = $this->post(route('admin.two-factor.verify'), ['code' => $code]);
    $response->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($administrator);
    $response->assertCookie(Auth::guard('web')->getRecallerName());
    $this->assertDatabaseMissing('admin_login_challenges', ['id' => $challengeId, 'consumed_at' => null]);

    Auth::logout();
    $this->withSession(['admin_login_challenge_id' => $challengeId])
        ->post(route('admin.two-factor.verify'), ['code' => $code])
        ->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

it('increments failed attempts and locks the challenge without storing the submitted code', function () {
    $administrator = User::factory()->admin()->create(['password' => 'safe-password']);
    $this->post(route('admin.login.store'), [
        'email' => $administrator->email,
        'password' => 'safe-password',
    ]);
    $challengeId = (string) session('admin_login_challenge_id');

    foreach (range(1, 4) as $attempt) {
        $this->post(route('admin.two-factor.verify'), ['code' => '000000'])
            ->assertRedirect()
            ->assertSessionHasErrors('code');
        $this->assertDatabaseHas('admin_login_challenges', ['id' => $challengeId, 'attempts' => $attempt, 'consumed_at' => null]);
    }

    $this->post(route('admin.two-factor.verify'), ['code' => '000000'])
        ->assertRedirect(route('admin.login'));
    $this->assertGuest();

    $record = DB::table('admin_login_challenges')->where('id', $challengeId)->first();
    expect($record->attempts)->toBe(5)
        ->and($record->consumed_at)->not->toBeNull();
    expect(json_encode(DB::table('admin_audit_logs')->get(), JSON_THROW_ON_ERROR))->not->toContain('000000');
});

it('rejects expired challenges and administrators revoked between both factors', function () {
    $administrator = User::factory()->admin()->create(['password' => 'safe-password']);
    $this->post(route('admin.login.store'), ['email' => $administrator->email, 'password' => 'safe-password']);
    $expiredId = (string) session('admin_login_challenge_id');
    DB::table('admin_login_challenges')->where('id', $expiredId)->update(['expires_at' => now()->subMinute()]);

    $this->post(route('admin.two-factor.verify'), ['code' => '123456'])
        ->assertRedirect(route('admin.login'));
    $this->assertGuest();

    $this->post(route('admin.login.store'), ['email' => $administrator->email, 'password' => 'safe-password']);
    $challengeId = (string) session('admin_login_challenge_id');
    $notification = Notification::sent($administrator, AdminTwoFactorCodeNotification::class)->last();
    expect($notification)->toBeInstanceOf(AdminTwoFactorCodeNotification::class);
    $code = $notification->code;
    $administrator->forceFill(['is_admin' => false, 'is_super_admin' => false])->save();

    $this->withSession(['admin_login_challenge_id' => $challengeId])
        ->post(route('admin.two-factor.verify'), ['code' => $code])
        ->assertRedirect(route('admin.login'));
    $this->assertGuest();
});

it('uses a generic rejection for invalid non-admin and unverified credentials', function (array $attributes) {
    $user = User::factory()->create(array_merge([
        'email' => 'candidate@example.com',
        'password' => 'safe-password',
    ], $attributes));

    $this->post(route('admin.login.store'), [
        'email' => $user->email,
        'password' => 'safe-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
    $this->assertDatabaseCount('admin_login_challenges', 0);
    Notification::assertNothingSent();
})->with([
    'customer' => [['is_admin' => false, 'is_super_admin' => false]],
    'unverified admin' => [['is_admin' => true, 'is_super_admin' => true, 'email_verified_at' => null]],
]);

it('invalidates the challenge when email delivery fails', function () {
    $administrator = User::factory()->admin()->create(['password' => 'safe-password']);
    $this->app->instance(AdminTwoFactorNotifier::class, new class implements AdminTwoFactorNotifier
    {
        public function send(int $userId, string $plainCode, DateTimeImmutable $expiresAt): void
        {
            throw new RuntimeException('Mail transport unavailable');
        }
    });

    $this->post(route('admin.login.store'), [
        'email' => $administrator->email,
        'password' => 'safe-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
    $record = DB::table('admin_login_challenges')->where('user_id', $administrator->id)->first();
    expect($record)->not->toBeNull()
        ->and($record->consumed_at)->not->toBeNull();
});

it('invalidates an older outstanding challenge when credentials are submitted again', function () {
    $administrator = User::factory()->admin()->create(['password' => 'safe-password']);
    $credentials = ['email' => $administrator->email, 'password' => 'safe-password'];

    $this->post(route('admin.login.store'), $credentials);
    $firstId = (string) session('admin_login_challenge_id');
    $this->get(route('admin.login'));
    $this->post(route('admin.login.store'), $credentials);
    $secondId = (string) session('admin_login_challenge_id');

    expect($secondId)->not->toBe($firstId);
    $this->assertDatabaseMissing('admin_login_challenges', ['id' => $firstId, 'consumed_at' => null]);
    $this->assertDatabaseHas('admin_login_challenges', ['id' => $secondId, 'consumed_at' => null]);
});

it('prunes challenges only after the configured retention period', function () {
    $administrator = User::factory()->admin()->create(['password' => 'safe-password']);
    $this->post(route('admin.login.store'), ['email' => $administrator->email, 'password' => 'safe-password']);
    $oldId = (string) session('admin_login_challenge_id');
    DB::table('admin_login_challenges')->where('id', $oldId)->update([
        'expires_at' => now()->subDays(31),
        'consumed_at' => now()->subDays(31),
    ]);

    $this->post(route('admin.login.store'), ['email' => $administrator->email, 'password' => 'safe-password']);
    $currentId = (string) session('admin_login_challenge_id');

    expect(app(PruneAdminLoginChallenges::class)->handle(30))->toBe(1);
    $this->assertDatabaseMissing('admin_login_challenges', ['id' => $oldId]);
    $this->assertDatabaseHas('admin_login_challenges', ['id' => $currentId]);
});

it('rate limits repeated second-factor guesses', function () {
    config()->set('security.admin_two_factor_max_attempts', 10);
    $administrator = User::factory()->admin()->create(['password' => 'safe-password']);
    $this->post(route('admin.login.store'), [
        'email' => $administrator->email,
        'password' => 'safe-password',
    ]);

    foreach (range(1, 10) as $attempt) {
        $this->post(route('admin.two-factor.verify'), ['code' => '000000'])->assertRedirect();
    }

    $this->withSession(['admin_login_challenge_id' => (string) DB::table('admin_login_challenges')->value('id')])
        ->post(route('admin.two-factor.verify'), ['code' => '000000'])
        ->assertTooManyRequests();
});

it('renders the challenge page only when a password challenge exists', function () {
    $this->get(route('admin.two-factor'))->assertRedirect(route('admin.login'));

    $administrator = User::factory()->admin()->create(['password' => 'safe-password']);
    $this->post(route('admin.login.store'), ['email' => $administrator->email, 'password' => 'safe-password']);

    $this->get(route('admin.two-factor'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('auth/two-factor')
        ->has('expiresAt'));
});
