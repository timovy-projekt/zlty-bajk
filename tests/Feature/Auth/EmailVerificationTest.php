<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

beforeEach(function (): void {
    Notification::fake();
});

test('email verification screen can be rendered', function (): void {
    $user = User::factory()->unverified()->create();

    $response = $this->actingAs($user)->get('/verify-email');

    $response->assertOk()->assertViewIs('auth.verify-email');
});

test('email can be verified', function (): void {
    $user = User::factory()->unverified()->create();

    Event::fake([Verified::class]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->email)]
    );

    $response = $this->actingAs($user)->get($verificationUrl);

    Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->is($user));
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $response->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    $this->get(route('dashboard'))->assertOk();
});

test('email is not verified with invalid hash', function (): void {
    $user = User::factory()->unverified()->create();

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1('wrong-email')]
    );

    Event::fake([Verified::class]);

    $this->actingAs($user)->get($verificationUrl)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    Event::assertNotDispatched(Verified::class);
});

test('unverified customers and administrators must verify before opening either dashboard', function (int $roleId): void {
    $user = User::factory()->unverified()->create(['role_id' => $roleId]);

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('verification.notice'));
    $this->get(route('verification.notice'))->assertOk();
})->with([
    'customer' => [User::ROLE_CUSTOMER],
    'admin' => [User::ROLE_ADMIN],
]);

test('unverified JSON requests cannot open the dashboard', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->getJson(route('dashboard'))->assertForbidden();
});

test('guests must log in before viewing verification or requesting another email', function (): void {
    $this->get(route('verification.notice'))->assertRedirect(route('login'));
    $this->post(route('verification.send'))->assertRedirect(route('login'));

    Notification::assertNothingSent();
});

test('a verification link can be completed after signing back in', function (): void {
    $user = User::factory()->unverified()->create();
    $verificationUrl = (new VerifyEmail)->toMail($user)->actionUrl;

    $this->get($verificationUrl)->assertRedirect(route('login'));
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect($verificationUrl);

    $this->get($verificationUrl)->assertRedirect(route('dashboard', absolute: false).'?verified=1');
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('verified customers and administrators are redirected away from the verification page', function (int $roleId): void {
    $user = User::factory()->create(['role_id' => $roleId]);

    $this->actingAs($user)->get(route('verification.notice'))->assertRedirect(route('dashboard'));

    if ($roleId === User::ROLE_ADMIN) {
        $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->get(route('admin.dashboard'))->assertOk();
    } else {
        $this->get(route('dashboard'))->assertOk();
        $this->get(route('admin.dashboard'))->assertForbidden();
    }
})->with([
    'customer' => [User::ROLE_CUSTOMER],
    'admin' => [User::ROLE_ADMIN],
]);

test('expired verification links cannot verify an email address', function (): void {
    $user = User::factory()->unverified()->create();
    Event::fake([Verified::class]);

    $verificationUrl = URL::temporarySignedRoute(
        'verification.verify',
        now()->subMinute(),
        ['id' => $user->id, 'hash' => sha1($user->email)],
    );

    $this->actingAs($user)->get($verificationUrl)->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    Event::assertNotDispatched(Verified::class);
});

test('tampered verification links cannot verify an email address', function (): void {
    $user = User::factory()->unverified()->create();
    Event::fake([Verified::class]);

    $verificationUrl = (new VerifyEmail)->toMail($user)->actionUrl;

    $this->actingAs($user)->get($verificationUrl.'&tampered=1')->assertForbidden();

    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
    Event::assertNotDispatched(Verified::class);
});

test('a verification link cannot verify another account', function (): void {
    $owner = User::factory()->unverified()->create();
    $otherUser = User::factory()->unverified()->create();
    Event::fake([Verified::class]);

    $verificationUrl = (new VerifyEmail)->toMail($owner)->actionUrl;

    $this->actingAs($otherUser)->get($verificationUrl)->assertForbidden();

    expect($owner->fresh()->hasVerifiedEmail())->toBeFalse()
        ->and($otherUser->fresh()->hasVerifiedEmail())->toBeFalse();
    Event::assertNotDispatched(Verified::class);
});

test('verification links do not change a verified timestamp or dispatch duplicate events when reused', function (): void {
    $user = User::factory()->unverified()->create();
    Event::fake([Verified::class]);

    $verificationUrl = (new VerifyEmail)->toMail($user)->actionUrl;

    $this->actingAs($user)->get($verificationUrl)->assertRedirect();
    $verifiedAt = $user->fresh()->email_verified_at;
    $this->travel(1)->minute();

    $this->get($verificationUrl)->assertRedirect();

    expect($user->fresh()->email_verified_at->equalTo($verifiedAt))->toBeTrue();
    Event::assertDispatchedTimes(Verified::class, 1);
});

test('unverified users can resend and complete a verification email', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->from(route('verification.notice'))
        ->post(route('verification.send'))
        ->assertRedirect(route('verification.notice'))
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentToOnce($user, VerifyEmail::class);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();

    $notification = Notification::sent($user, VerifyEmail::class)->sole();

    $this->get($notification->toMail($user)->actionUrl)->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('verified users are not sent another verification email', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('verification.send'))->assertRedirect(route('dashboard'));

    Notification::assertNothingSent();
});

test('verification email resends are limited to six requests per minute', function (): void {
    $user = User::factory()->unverified()->create();
    $this->actingAs($user)->from(route('verification.notice'));

    for ($attempt = 0; $attempt < 6; $attempt++) {
        $this->post(route('verification.send'))->assertRedirect(route('verification.notice'));
    }

    $this->post(route('verification.send'))->assertTooManyRequests();
    Notification::assertSentToTimes($user, VerifyEmail::class, 6);
    expect($user->fresh()->hasVerifiedEmail())->toBeFalse();
});

test('changing an email requires verification of the new address and invalidates the old link', function (): void {
    $user = User::factory()->create();
    $oldVerificationUrl = (new VerifyEmail)->toMail($user)->actionUrl;

    $this->actingAs($user)->patch(route('profile.update'), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => 'new-address@example.com',
        'email_verified_at' => now()->toDateTimeString(),
    ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

    expect($user->fresh()->email_verified_at)->toBeNull();
    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    $this->get($oldVerificationUrl)->assertForbidden();

    $this->from(route('profile.edit'))->post(route('verification.send'))
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'verification-link-sent');

    $user->refresh();
    expect($user->getEmailForVerification())->toBe('new-address@example.com');
    Notification::assertSentToOnce($user, VerifyEmail::class);
    $notification = Notification::sent($user, VerifyEmail::class)->sole();

    $this->get($notification->toMail($user)->actionUrl)->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->get(route('dashboard'))->assertOk();
});
