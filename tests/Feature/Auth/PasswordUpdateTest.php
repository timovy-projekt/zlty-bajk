<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

test('a password update rejects the old password and accepts the new password after logout', function (): void {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors();
    $this->assertAuthenticatedAs($user);

    $response = $this
        ->from('/profile')
        ->put('/password', [
            'current_password' => 'password',
            'password' => 'new-password',
            'password_confirmation' => 'new-password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', 'password-updated')
        ->assertRedirect('/profile');

    $this->assertTrue(Hash::check('new-password', $user->refresh()->password));
    $this->assertFalse(Hash::check('password', $user->password));

    $this->post(route('logout'))->assertRedirect('/');
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

test('a failed password update preserves the original password', function (array $passwordData, string $errorField): void {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->put('/password', $passwordData);

    $response
        ->assertSessionHasErrorsIn('updatePassword', $errorField)
        ->assertSessionMissing('status')
        ->assertRedirect('/profile');

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue()
        ->and(Hash::check('new-password', $user->password))->toBeFalse();
})->with([
    'incorrect current password' => [[
        'current_password' => 'wrong-password',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ], 'current_password'],
    'mismatched confirmation' => [[
        'current_password' => 'password',
        'password' => 'new-password',
        'password_confirmation' => 'different-password',
    ], 'password'],
    'password below the minimum length' => [[
        'current_password' => 'password',
        'password' => 'short',
        'password_confirmation' => 'short',
    ], 'password'],
]);
