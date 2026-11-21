<?php

use App\Models\User;

test('guests are redirected away from the rental application administration', function (): void {
    $this->get(route('admin.rental-applications.index'))->assertRedirect(route('login'));
});

test('customers cannot open the rental application administration', function (): void {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.rental-applications.index'))
        ->assertForbidden();
});

test('administrators see the rental application list wired to the admin api', function (): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.rental-applications.index'))
        ->assertOk()
        ->assertSee('Žiadosti o prenájom')
        ->assertSee(route('api.v1.admin.rental-applications.index'), false);
});

test('customers see their applications tab wired to the customer api', function (): void {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('dashboard', ['tab' => 'applications']))
        ->assertOk()
        ->assertSee('Moje žiadosti')
        ->assertSee(route('api.v1.rental-applications.index'), false)
        ->assertDontSee(route('api.v1.admin.rental-applications.index'), false);
});
