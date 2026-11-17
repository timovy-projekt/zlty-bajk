<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

test('reset password link screen can be rendered', function () {
    $response = $this->get('/forgot-password');

    $response->assertStatus(200);
});

test('reset password link can be requested', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class);
});

test('reset password screen can be rendered', function () {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentTo($user, ResetPassword::class, function ($notification) {
        $response = $this->get('/reset-password/'.$notification->token);

        $response->assertStatus(200);

        return true;
    });
});

test('a password reset rejects the old password and accepts the new password', function (): void {
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email]);

    Notification::assertSentToOnce($user, ResetPassword::class);
    $notification = Notification::sent($user, ResetPassword::class)->sole();

    $this->post('/reset-password', [
        'token' => $notification->token,
        'email' => $user->email,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue()
        ->and(Hash::check('password', $user->password))->toBeFalse();

    Auth::forgetGuards();
    $this->assertGuest();
    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'new-password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
});
