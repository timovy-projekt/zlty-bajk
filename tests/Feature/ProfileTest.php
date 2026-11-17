<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk()
        ->assertSee('Deactivate Account')
        ->assertDontSee('permanently deleted');
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('Test', $user->first_name);
    $this->assertSame('User', $user->last_name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('account deactivation preserves the user and prevents another login', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $originalDetails = $user->only('first_name', 'last_name', 'email', 'password', 'role_id');
    $rememberToken = $user->remember_token;

    $response = $this
        ->actingAs($user)
        ->withSession(['private-profile-data' => 'value'])
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionMissing('private-profile-data')
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 2);
    expect($user->fresh()->is_active)->toBeFalse()
        ->and($user->fresh()->only(array_keys($originalDetails)))->toBe($originalDetails)
        ->and($user->fresh()->remember_token)->not->toBe($rememberToken)
        ->and($otherUser->fresh()->is_active)->toBeTrue();

    Auth::forgetGuards();
    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasErrors('email');
    $this->assertGuest();
});

test('correct password must be provided to deactivate an account', function (?string $password): void {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => $password,
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    expect($user->fresh()->is_active)->toBeTrue();
    $this->assertAuthenticatedAs($user);
})->with(['wrong-password', '', null]);

test('a deactivated account cannot update its profile using an existing session', function (): void {
    $user = User::factory()->create();
    $originalEmail = $user->email;

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();

    $user->is_active = false;
    $user->save();
    Auth::forgetGuards();

    $this->patch(route('profile.update'), [
        'first_name' => 'Changed',
        'last_name' => 'Name',
        'email' => 'changed@example.com',
    ])->assertRedirect(route('login'));

    $this->assertGuest();
    expect($user->fresh()->email)->toBe($originalEmail)
        ->and($user->fresh()->is_active)->toBeFalse();
});
