<?php

use App\Models\User;
use App\Models\UserGuardian;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
    $this->travelTo(now()->setDate(2026, 11, 5)->setTime(12, 0));

    $this->minorRegistration = [
        'first_name' => 'Test',
        'last_name' => 'Customer',
        'email' => 'customer@example.com',
        'phone' => '+421 900 123 456',
        'street_address' => 'Customer Street 1',
        'city' => 'Trnava',
        'postal_code' => '917 01',
        'date_of_birth' => '2010-11-05',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
        'gdpr_consent' => '1',
        'guardian_first_name' => 'Test',
        'guardian_last_name' => 'Guardian',
        'guardian_email' => 'guardian@example.com',
        'guardian_phone' => '+421 900 654 321',
        'guardian_street_address' => 'Guardian Street 2',
        'guardian_city' => 'Bratislava',
        'guardian_postal_code' => '811 01',
        'guardian_date_of_birth' => '1980-07-08',
    ];
});

test('minor registration saves guardian details using the database team schema', function (): void {
    $otherUser = User::factory()->create();

    $this->post('/register', array_replace($this->minorRegistration, [
        'guardian_user_id' => $otherUser->id,
        'guardian_role_id' => User::ROLE_ADMIN,
    ]))->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'customer@example.com')->sole();
    $guardian = UserGuardian::sole();

    $this->assertAuthenticatedAs($user);
    $this->assertDatabaseHas('user_guardians', [
        'user_id' => $user->id,
        'first_name' => 'Test',
        'last_name' => 'Guardian',
        'email' => 'guardian@example.com',
        'phone' => '+421 900 654 321',
        'street_address' => 'Guardian Street 2',
        'city' => 'Bratislava',
        'postal_code' => '811 01',
        'id_number' => null,
    ]);

    expect($user->guardian->is($guardian))->toBeTrue()
        ->and($guardian->user->is($user))->toBeTrue()
        ->and($guardian->date_of_birth->toDateString())->toBe('1980-07-08')
        ->and($user->role_id)->toBe(User::ROLE_CUSTOMER);

    $this->assertDatabaseCount('users', 2);
    $this->assertDatabaseMissing('users', ['email' => 'guardian@example.com']);
});

test('same address checkbox copies the customer address on the server', function (): void {
    $this->post('/register', array_replace($this->minorRegistration, [
        'guardian_same_address' => '1',
        'guardian_street_address' => ['ignored'],
        'guardian_city' => ['ignored'],
        'guardian_postal_code' => ['ignored'],
    ]))->assertSessionHasNoErrors();

    $this->assertDatabaseHas('user_guardians', [
        'street_address' => 'Customer Street 1',
        'city' => 'Trnava',
        'postal_code' => '917 01',
    ]);
});

test('unchecked same address checkbox preserves the separate guardian address', function (): void {
    $this->post('/register', array_replace($this->minorRegistration, [
        'guardian_same_address' => '0',
    ]))->assertSessionHasNoErrors();

    expect(UserGuardian::sole()->city)->toBe('Bratislava');
});

test('optional guardian columns and both ID numbers can be omitted', function (): void {
    $data = $this->minorRegistration;

    foreach (['phone', 'street_address', 'city', 'postal_code', 'id_number'] as $field) {
        unset($data['guardian_'.$field]);
    }

    $this->post('/register', $data)->assertSessionHasNoErrors();

    $guardian = UserGuardian::sole();
    expect(User::sole()->id_number)->toBeNull()
        ->and($guardian->phone)->toBeNull()
        ->and($guardian->street_address)->toBeNull()
        ->and($guardian->city)->toBeNull()
        ->and($guardian->postal_code)->toBeNull()
        ->and($guardian->date_of_birth->toDateString())->toBe('1980-07-08')
        ->and($guardian->id_number)->toBeNull();
});

test('empty optional ID numbers are stored as null', function (?string $idNumber): void {
    $this->post('/register', array_replace($this->minorRegistration, [
        'id_number' => $idNumber,
        'guardian_id_number' => $idNumber,
    ]))->assertSessionHasNoErrors();

    expect(User::sole()->id_number)->toBeNull()
        ->and(UserGuardian::sole()->id_number)->toBeNull();
})->with([null, '']);

test('adult registration ignores all guardian data', function (): void {
    $data = $this->minorRegistration;

    foreach (array_keys($data) as $field) {
        if (str_starts_with($field, 'guardian_')) {
            $data[$field] = ['ignored'];
        }
    }

    $this->post('/register', array_replace($data, [
        'date_of_birth' => '2008-11-05',
        'guardian_same_address' => ['ignored'],
        'guardian_id_number' => ['ignored'],
    ]))->assertSessionHasNoErrors();

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('user_guardians', 0);
});

test('minor registration validates guardian columns before storing either record', function (string $field, mixed $value): void {
    $this->post('/register', array_replace($this->minorRegistration, [
        $field => $value,
    ]))->assertSessionHasErrors($field);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('user_guardians', 0);
    Notification::assertNothingSent();
})->with([
    'missing guardian email' => ['guardian_email', ''],
    'invalid guardian email' => ['guardian_email', 'invalid'],
    'long guardian email' => ['guardian_email', str_repeat('a', 243).'@example.com'],
    'guardian email is not text' => ['guardian_email', ['guardian@example.com']],
    'invalid phone' => ['guardian_phone', 'not a phone'],
    'long phone' => ['guardian_phone', str_repeat('1', 31)],
    'long street' => ['guardian_street_address', str_repeat('a', 256)],
    'long city' => ['guardian_city', str_repeat('a', 101)],
    'long postal code' => ['guardian_postal_code', str_repeat('1', 21)],
    'invalid birth date' => ['guardian_date_of_birth', 'invalid'],
    'empty birth date' => ['guardian_date_of_birth', ''],
    'null birth date' => ['guardian_date_of_birth', null],
    'birth date is not text' => ['guardian_date_of_birth', ['1980-07-08']],
    'impossible birth date' => ['guardian_date_of_birth', '1980-02-30'],
    'date outside MySQL range' => ['guardian_date_of_birth', '0999-12-31'],
    'future birth date' => ['guardian_date_of_birth', '2026-11-06'],
    'invalid same address flag' => ['guardian_same_address', 'invalid'],
]);

test('minor registration requires the guardian birth date when the field is omitted', function (): void {
    $data = $this->minorRegistration;
    unset($data['guardian_date_of_birth']);

    $this->post('/register', $data)->assertSessionHasErrors('guardian_date_of_birth');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('user_guardians', 0);
    Notification::assertNothingSent();
});

test('the database rejects a guardian without a birth date', function (): void {
    expect(fn () => UserGuardian::factory()->create(['date_of_birth' => null]))
        ->toThrow(QueryException::class);
});

test('registration rolls back the customer when the guardian cannot be saved', function (): void {
    UserGuardian::creating(function (): void {
        throw new RuntimeException('Simulated guardian insert failure.');
    });

    try {
        $this->post('/register', $this->minorRegistration)->assertServerError();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('user_guardians', 0);
        $this->assertGuest();
        Notification::assertNothingSent();
    } finally {
        UserGuardian::flushEventListeners();
    }
});

test('account deletion removes its guardian before deleting the customer', function (): void {
    $guardian = UserGuardian::factory()->create();
    $user = $guardian->user;

    $this->actingAs($user)->delete('/profile', [
        'password' => 'password',
    ])->assertSessionHasNoErrors()->assertRedirect('/');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    $this->assertDatabaseCount('user_guardians', 0);
});

test('account deletion preserves both records when the password is incorrect', function (): void {
    $guardian = UserGuardian::factory()->create();

    $this->actingAs($guardian->user)->delete('/profile', [
        'password' => 'wrong-password',
    ])->assertSessionHasErrorsIn('userDeletion', 'password');

    $this->assertDatabaseCount('users', 1);
    $this->assertDatabaseCount('user_guardians', 1);
});

test('account deletion rolls back its guardian when the customer cannot be deleted', function (): void {
    $guardian = UserGuardian::factory()->create();

    User::deleting(function (): void {
        throw new RuntimeException('Simulated customer delete failure.');
    });

    try {
        $this->actingAs($guardian->user)->delete('/profile', [
            'password' => 'password',
        ])->assertServerError();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseCount('user_guardians', 1);
    } finally {
        User::flushEventListeners();
    }
});

test('the same guardian email can represent multiple customers', function (): void {
    UserGuardian::factory()->create(['email' => 'guardian@example.com']);

    $this->post('/register', $this->minorRegistration)->assertSessionHasNoErrors();

    $this->assertDatabaseCount('users', 2);
    expect(UserGuardian::where('email', 'guardian@example.com')->count())->toBe(2);
});
