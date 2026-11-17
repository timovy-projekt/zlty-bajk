<?php

use App\Models\RentalApplication;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->createRentalApplicationsTable();
    $this->travelTo(now()->setDate(2026, 11, 17)->setTime(12, 0));
});

test('the customer controller lists only owned applications newest first without private notes', function (): void {
    $customer = User::factory()->create();
    $newer = RentalApplication::factory()->for($customer)->create([
        'submitted_at' => now()->subDay(),
        'notes' => 'Private review note',
    ]);
    $older = RentalApplication::factory()->for($customer)->create(['submitted_at' => now()->subDays(2)]);
    RentalApplication::factory()->create();

    $response = $this->actingAs($customer)
        ->getJson(route('api.v1.rental-applications.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonMissingPath('data.0.notes')
        ->assertJsonMissingPath('data.0.customer');

    expect(array_column($response->json('data'), 'id'))->toBe([$newer->id, $older->id]);
});

test('the customer controller shows an owned application without changing it or exposing private notes', function (): void {
    $application = RentalApplication::factory()->create(['notes' => 'Private review note']);
    $originalAttributes = $application->fresh()->getAttributes();

    $this->actingAs($application->user)
        ->getJson(route('api.v1.rental-applications.show', $application))
        ->assertOk()
        ->assertJsonPath('data.id', $application->id)
        ->assertJsonPath('data.user_id', $application->user_id)
        ->assertJsonPath('data.status', 'submitted')
        ->assertJsonPath('data.reviewed_at', null)
        ->assertJsonPath('data.submitted_at', $application->submitted_at->toIso8601String())
        ->assertJsonMissingPath('data.notes')
        ->assertJsonMissingPath('data.customer');

    expect($application->fresh()->getAttributes())->toBe($originalAttributes);
});

test('the customer controller returns not found for foreign or missing application ids', function (bool $missing): void {
    $customer = User::factory()->create();
    $foreignApplication = RentalApplication::factory()->create();
    $applicationId = $missing ? $foreignApplication->id + 1 : $foreignApplication->id;

    $this->actingAs($customer)
        ->getJson(route('api.v1.rental-applications.show', $applicationId))
        ->assertNotFound()
        ->assertDontSee($foreignApplication->user->email);
})->with(['foreign application' => false, 'missing application' => true]);

test('the administrator controller lists applications from all customers with limited customer details', function (): void {
    $admin = User::factory()->admin()->create();
    $older = RentalApplication::factory()->create(['submitted_at' => now()->subDays(2)]);
    $newer = RentalApplication::factory()->create([
        'submitted_at' => now()->subDay(),
        'notes' => 'Review pending',
    ]);

    $response = $this->actingAs($admin)
        ->getJson(route('api.v1.admin.rental-applications.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2)
        ->assertJsonPath('data.0.customer.id', $newer->user_id)
        ->assertJsonPath('data.0.customer.email', $newer->user->email)
        ->assertJsonPath('data.0.notes', 'Review pending')
        ->assertJsonMissingPath('data.0.customer.password')
        ->assertJsonMissingPath('data.0.customer.remember_token');

    expect(array_column($response->json('data'), 'id'))->toBe([$newer->id, $older->id]);
});

test('the administrator controller shows a reviewed application without changing it', function (): void {
    $admin = User::factory()->admin()->create();
    $application = RentalApplication::factory()->for($admin, 'reviewer')->create([
        'status' => RentalApplication::STATUS_REJECTED,
        'reviewed_at' => now(),
        'notes' => 'No suitable bicycle available',
    ]);
    $originalAttributes = $application->fresh()->getAttributes();

    $this->actingAs($admin)
        ->getJson(route('api.v1.admin.rental-applications.show', $application))
        ->assertOk()
        ->assertJsonPath('data.id', $application->id)
        ->assertJsonPath('data.status', 'rejected')
        ->assertJsonPath('data.reviewed_by', $admin->id)
        ->assertJsonPath('data.reviewed_at', $application->reviewed_at->toIso8601String())
        ->assertJsonPath('data.notes', 'No suitable bicycle available')
        ->assertJsonPath('data.customer.id', $application->user_id)
        ->assertJsonMissingPath('data.customer.password')
        ->assertJsonMissingPath('data.customer.remember_token');

    expect($application->fresh()->getAttributes())->toBe($originalAttributes);
});

test('rental application controllers reject the wrong role', function (bool $administrator, string $routeName, bool $detail): void {
    $user = $administrator ? User::factory()->admin()->create() : User::factory()->create();
    $application = RentalApplication::factory()->for($user)->create();

    $this->actingAs($user)
        ->getJson(route($routeName, $detail ? [$application] : []))
        ->assertForbidden();
})->with([
    'customer opening administrator list' => [false, 'api.v1.admin.rental-applications.index', false],
    'customer opening administrator detail' => [false, 'api.v1.admin.rental-applications.show', true],
    'administrator opening customer list' => [true, 'api.v1.rental-applications.index', false],
    'administrator opening customer detail' => [true, 'api.v1.rental-applications.show', true],
]);

test('rental application controllers require authentication', function (string $routeName, bool $detail): void {
    $application = RentalApplication::factory()->create();

    $this->get(route($routeName, $detail ? [$application] : []))
        ->assertUnauthorized()
        ->assertHeader('Content-Type', 'application/json');
})->with([
    'customer list' => ['api.v1.rental-applications.index', false],
    'customer detail' => ['api.v1.rental-applications.show', true],
    'administrator list' => ['api.v1.admin.rental-applications.index', false],
    'administrator detail' => ['api.v1.admin.rental-applications.show', true],
]);

test('rental application controllers return an empty paginated list when there are no applications', function (bool $administrator, string $routeName): void {
    $user = $administrator ? User::factory()->admin()->create() : User::factory()->create();

    $this->actingAs($user)
        ->getJson(route($routeName))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
})->with([
    'customer' => [false, 'api.v1.rental-applications.index'],
    'administrator' => [true, 'api.v1.admin.rental-applications.index'],
]);

test('customer pagination counts and returns only owned applications', function (): void {
    $customer = User::factory()->create();
    $applications = RentalApplication::factory()->count(21)->for($customer)->create();
    RentalApplication::factory()->create();

    $this->actingAs($customer)
        ->getJson(route('api.v1.rental-applications.index'))
        ->assertOk()
        ->assertJsonCount(20, 'data')
        ->assertJsonPath('meta.total', 21)
        ->assertJsonPath('meta.per_page', 20);

    $this->getJson(route('api.v1.rental-applications.index', ['page' => 2]))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('data.0.id', $applications->first()->id);
});

test('application status filters preserve the scope of the customer and administrator lists', function (string $status, bool $administrator): void {
    $customer = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $applications = collect(['submitted', 'approved', 'rejected', 'cancelled'])
        ->mapWithKeys(fn (string $applicationStatus): array => [
            $applicationStatus => RentalApplication::factory()->for($customer)->create(['status' => $applicationStatus]),
        ]);
    $foreignApplication = RentalApplication::factory()->create(['status' => $status]);
    $routeName = $administrator ? 'api.v1.admin.rental-applications.index' : 'api.v1.rental-applications.index';

    $response = $this->actingAs($administrator ? $admin : $customer)
        ->getJson(route($routeName, ['status' => $status]))
        ->assertOk()
        ->assertJsonCount($administrator ? 2 : 1, 'data')
        ->assertJsonPath('meta.total', $administrator ? 2 : 1);

    $expectedIds = $administrator
        ? [$foreignApplication->id, $applications[$status]->id]
        : [$applications[$status]->id];

    expect(array_column($response->json('data'), 'id'))->toBe($expectedIds)
        ->and(array_unique(array_column($response->json('data'), 'status')))->toBe([$status]);
})->with(['submitted', 'approved', 'rejected', 'cancelled'])
    ->with(['customer' => false, 'administrator' => true]);

test('custom pagination retains supported filters and ignores a forged owner parameter', function (bool $administrator): void {
    $customer = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $ownedApplications = RentalApplication::factory()->count(3)->for($customer)->create();
    $foreignApplication = RentalApplication::factory()->create();
    $routeName = $administrator ? 'api.v1.admin.rental-applications.index' : 'api.v1.rental-applications.index';

    $response = $this->actingAs($administrator ? $admin : $customer)
        ->getJson(route($routeName, [
            'status' => 'submitted',
            'per_page' => 2,
            'user_id' => $foreignApplication->user_id,
        ]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.per_page', 2)
        ->assertJsonPath('meta.total', $administrator ? 4 : 3);

    parse_str(parse_url($response->json('links.next'), PHP_URL_QUERY), $nextPageParameters);

    expect($nextPageParameters)->toMatchArray(['status' => 'submitted', 'per_page' => '2', 'page' => '2'])
        ->not->toHaveKey('user_id');

    if (! $administrator) {
        expect(array_column($response->json('data'), 'user_id'))->toBe([$customer->id, $customer->id]);
    }

    $this->getJson($response->json('links.next'))
        ->assertOk()
        ->assertJsonPath('meta.current_page', 2)
        ->assertJsonPath('data.0.id', $administrator ? $ownedApplications[1]->id : $ownedApplications[0]->id);
})->with(['customer' => false, 'administrator' => true]);

test('an empty status filter returns all owned statuses', function (): void {
    $customer = User::factory()->create();
    RentalApplication::factory()->for($customer)->create();
    RentalApplication::factory()->for($customer)->create(['status' => 'cancelled']);
    RentalApplication::factory()->create();

    $this->actingAs($customer)
        ->getJson(route('api.v1.rental-applications.index', ['status' => '']))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.total', 2);
});

test('page sizes at both supported boundaries are accepted', function (int $perPage): void {
    $customer = User::factory()->create();
    $admin = User::factory()->admin()->create();
    RentalApplication::factory()->count(3)->for($customer)->create();

    foreach ([
        [$customer, 'api.v1.rental-applications.index'],
        [$admin, 'api.v1.admin.rental-applications.index'],
    ] as [$user, $routeName]) {
        $this->actingAs($user)
            ->getJson(route($routeName, ['per_page' => $perPage]))
            ->assertOk()
            ->assertJsonCount(min(3, $perPage), 'data')
            ->assertJsonPath('meta.per_page', $perPage);
    }
})->with([1, 100]);

test('list endpoints reject invalid filters and pagination with JSON validation errors', function (array $query, string $field): void {
    $customer = User::factory()->create();
    $admin = User::factory()->admin()->create();

    foreach ([
        [$customer, 'api.v1.rental-applications.index'],
        [$admin, 'api.v1.admin.rental-applications.index'],
    ] as [$user, $routeName]) {
        $this->actingAs($user)->get(route($routeName, $query))
            ->assertUnprocessable()
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonValidationErrors($field);
    }
})->with([
    'unknown status' => [['status' => 'unknown'], 'status'],
    'status array' => [['status' => ['submitted']], 'status'],
    'page size below minimum' => [['per_page' => 0], 'per_page'],
    'page size above maximum' => [['per_page' => 101], 'per_page'],
    'page size array' => [['per_page' => [20]], 'per_page'],
    'invalid page' => [['page' => -1], 'page'],
]);

test('inactive and unverified accounts cannot read application endpoints even without a JSON accept header', function (bool $administrator, string $routeName, bool $detail, string $accountState): void {
    $user = User::factory()->create([
        'role_id' => $administrator ? User::ROLE_ADMIN : User::ROLE_CUSTOMER,
        'is_active' => $accountState !== 'inactive',
        'email_verified_at' => $accountState === 'unverified' ? null : now(),
    ]);
    $application = RentalApplication::factory()->for($user)->create();

    $this->actingAs($user)
        ->get(route($routeName, $detail ? [$application] : []))
        ->assertForbidden()
        ->assertHeader('Content-Type', 'application/json');

    if ($accountState === 'inactive') {
        $this->assertGuest();
    } else {
        $this->assertAuthenticatedAs($user);
    }
})->with([
    'customer list' => [false, 'api.v1.rental-applications.index', false],
    'customer detail' => [false, 'api.v1.rental-applications.show', true],
    'administrator list' => [true, 'api.v1.admin.rental-applications.index', false],
    'administrator detail' => [true, 'api.v1.admin.rental-applications.show', true],
])->with(['inactive', 'unverified']);

test('unknown roles cannot read either application list or detail', function (): void {
    DB::table('user_roles')->insert(['id' => 3, 'code' => 'other', 'name' => 'Other']);
    $user = User::factory()->create(['role_id' => 3]);
    $application = RentalApplication::factory()->for($user)->create();

    foreach ([
        'api.v1.rental-applications.index',
        'api.v1.rental-applications.show',
        'api.v1.admin.rental-applications.index',
        'api.v1.admin.rental-applications.show',
    ] as $routeName) {
        $detail = str_ends_with($routeName, '.show');

        $this->actingAs($user)->getJson(route($routeName, $detail ? [$application] : []))->assertForbidden();
    }
});

test('the administrator detail returns not found for missing or nonnumeric ids', function (string $applicationId): void {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->getJson(route('api.v1.admin.rental-applications.show', $applicationId))
        ->assertNotFound();
})->with(['missing' => '999999', 'nonnumeric' => 'invalid']);

test('application list requests do not modify stored applications', function (): void {
    $customer = User::factory()->create();
    $admin = User::factory()->admin()->create();
    RentalApplication::factory()->count(2)->for($customer)->create();
    RentalApplication::factory()->create();
    $originalRows = DB::table('rental_applications')->orderBy('id')->get()->toArray();

    $this->actingAs($customer)->getJson(route('api.v1.rental-applications.index'))->assertOk();
    $this->actingAs($admin)->getJson(route('api.v1.admin.rental-applications.index'))->assertOk();

    expect(DB::table('rental_applications')->orderBy('id')->get()->toArray())->toEqual($originalRows);
});
