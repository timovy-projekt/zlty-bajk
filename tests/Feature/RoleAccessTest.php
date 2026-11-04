<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;

test('guests must log in before opening the administrator dashboard', function (): void {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

test('customers see their own dashboard', function (): void {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertViewIs('dashboard')
        ->assertSee('Customer dashboard');
});

test('customers cannot open the administrator dashboard directly', function (): void {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('administrators use the same login and reach the administrator dashboard', function (): void {
    $admin = User::factory()->admin()->create();

    $this->post(route('login'), [
        'email' => $admin->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($admin);

    $this->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertViewIs('admin-dashboard')
        ->assertSee('Administrator dashboard');
});

test('inactive accounts cannot access either dashboard with an existing session', function (int $roleId): void {
    $user = User::factory()->create(['role_id' => $roleId, 'is_active' => false]);

    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    $this->get(route('admin.dashboard'))->assertForbidden();
})->with([
    'customer' => [User::ROLE_CUSTOMER],
    'admin' => [User::ROLE_ADMIN],
]);

test('unrecognized roles have no dashboard access', function (): void {
    DB::table('user_roles')->insert(['id' => 3, 'code' => 'other', 'name' => 'Other']);
    $user = User::factory()->create(['role_id' => 3]);

    $this->actingAs($user)->get(route('dashboard'))->assertForbidden();
    $this->get(route('admin.dashboard'))->assertForbidden();
});

test('customers cannot promote themselves through the profile endpoint', function (): void {
    $customer = User::factory()->create();

    $this->actingAs($customer)->patch(route('profile.update'), [
        'first_name' => $customer->first_name,
        'last_name' => $customer->last_name,
        'email' => $customer->email,
        'role_id' => User::ROLE_ADMIN,
        'is_active' => false,
    ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

    expect($customer->refresh()->role_id)->toBe(User::ROLE_CUSTOMER)
        ->and($customer->is_active)->toBeTrue();

    $this->get(route('admin.dashboard'))->assertForbidden();
});
