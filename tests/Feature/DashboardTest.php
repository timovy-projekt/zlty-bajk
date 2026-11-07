<?php

use App\Models\User;

test('guests are redirected away from the dashboard', function (): void {
    $this->get('/dashboard')->assertRedirect('/login');
});

test('the dashboard displays the authenticated customers personal details', function (): void {
    $user = User::factory()->create([
        'first_name' => 'Anna',
        'last_name' => 'Testova',
        'email' => 'anna@example.com',
        'phone' => '+421 900 123 456',
        'street_address' => 'Test Street 1',
        'city' => 'Trnava',
        'postal_code' => '917 01',
        'date_of_birth' => '1994-06-12',
        'id_number' => 'AB123456',
        'created_at' => '2026-10-29 09:00:00',
    ]);
    $otherUser = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk();
    $response->assertSee('Osobné údaje');
    $response->assertSee('Ulica, č. domu');
    $response->assertSee('Anna');
    $response->assertSee('Testova');
    $response->assertSee('anna@example.com');
    $response->assertSee('Test Street 1');
    $response->assertSee('AB123456');
    $response->assertSee('12.06.1994');
    $response->assertSee('29.10.2026');
    $response->assertSee('Overený');
    $response->assertDontSee('Neoverený');
    $response->assertDontSee($otherUser->email);
});

test('the dashboard displays the documents card when the documents tab is selected', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/dashboard?tab=documents');

    $response->assertOk();
    $response->assertSee('Nájomná zmluva');
    $response->assertSee(route('documents.rental'), false);
    $response->assertSee(route('documents.vop'), false);
    $response->assertSee(route('documents.gdpr'), false);
    $response->assertDontSee('Ulica, č. domu');
});

test('the dashboard supports existing accounts without optional personal details', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee($user->first_name)
        ->assertSee('Overený')
        ->assertViewHas('profile', fn ($profile): bool => $profile['date_of_birth'] === null && $profile['id_number'] === null);
});
