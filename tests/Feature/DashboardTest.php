<?php

use App\Models\User;

test('guests are redirected away from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('the dashboard displays the personal details card', function () {
    // The users table migration is currently disabled, so the user is built
    // instead of persisted. The dashboard renders placeholder data for now.
    $user = User::factory()->make(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Osobné údaje');
    $response->assertSee('Ulica, č. domu');
});

test('the dashboard displays the documents card when the documents tab is selected', function () {
    $user = User::factory()->make(['email_verified_at' => now()]);

    $response = $this->actingAs($user)->get('/dashboard?tab=documents');

    $response->assertOk();
    $response->assertSee('Dokumenty sa zobrazia na tomto mieste.');
    $response->assertDontSee('Ulica, č. domu');
});
