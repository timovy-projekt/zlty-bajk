<?php

use App\Models\RentalApplication;
use App\Models\User;
use Carbon\CarbonInterface;

beforeEach(function (): void {
    $this->createRentalApplicationsTable();
    $this->travelTo(now()->setDate(2026, 11, 17)->setTime(12, 0));
});

test('a submitted rental application belongs to its customer and has no review yet', function (): void {
    $application = RentalApplication::factory()->create()->fresh();

    expect($application->user)->toBeInstanceOf(User::class)
        ->and($application->user->isCustomer())->toBeTrue()
        ->and($application->user_id)->toBe($application->user->id)
        ->and($application->status)->toBe('submitted')
        ->and($application->submitted_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($application->submitted_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($application->reviewed_at)->toBeNull()
        ->and($application->reviewed_by)->toBeNull()
        ->and($application->reviewer)->toBeNull();
});

test('a reviewed rental application resolves its reviewer separately from its customer and casts both dates', function (): void {
    $customer = User::factory()->create();
    $reviewer = User::factory()->admin()->create();

    $application = RentalApplication::factory()
        ->for($customer)
        ->for($reviewer, 'reviewer')
        ->create([
            'status' => RentalApplication::STATUS_APPROVED,
            'submitted_at' => '2026-11-16 10:15:00',
            'reviewed_at' => '2026-11-17 11:30:00',
        ])->fresh();

    expect($application->user->is($customer))->toBeTrue()
        ->and($application->reviewer->is($reviewer))->toBeTrue()
        ->and($application->reviewed_by)->toBe($reviewer->id)
        ->and($application->status)->toBe('approved')
        ->and($application->submitted_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($application->submitted_at->toDateTimeString())->toBe('2026-11-16 10:15:00')
        ->and($application->reviewed_at)->toBeInstanceOf(CarbonInterface::class)
        ->and($application->reviewed_at->toDateTimeString())->toBe('2026-11-17 11:30:00');
});

test('users have only their own rental applications and reviewing an application does not make it their own', function (): void {
    $customer = User::factory()->create();
    $otherCustomer = User::factory()->create();
    $reviewer = User::factory()->admin()->create();

    $ownedApplications = RentalApplication::factory()->count(2)->for($customer)->create();
    $otherApplication = RentalApplication::factory()
        ->for($otherCustomer)
        ->for($reviewer, 'reviewer')
        ->create([
            'status' => RentalApplication::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);

    expect($customer->rentalApplications->modelKeys())->toEqualCanonicalizing($ownedApplications->modelKeys())
        ->and($otherCustomer->rentalApplications->modelKeys())->toBe([$otherApplication->id])
        ->and($reviewer->rentalApplications)->toBeEmpty();
});
