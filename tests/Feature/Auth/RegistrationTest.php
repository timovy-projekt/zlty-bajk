<?php

use App\Models\User;
use App\Models\UserGuardian;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    $this->travelTo(now()->setDate(2026, 11, 1)->setTime(12, 0));

    $this->registrationData = [
        'first_name' => 'Test',
        'last_name' => 'User',
        'phone' => '+421 900 123 456',
        'email' => 'test@example.com',
        'street_address' => 'J. Herdu 1',
        'city' => 'Trnava',
        'postal_code' => '917 01',
        'date_of_birth' => '2000-01-01',
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms' => '1',
        'gdpr_consent' => '1',
    ];

    $this->guardianData = [
        'guardian_first_name' => 'Guardian',
        'guardian_last_name' => 'Person',
        'guardian_email' => 'guardian@example.com',
        'guardian_date_of_birth' => '1980-01-01',
        'guardian_id_number' => 'AA123456',
    ];
});

test('registration screen can be rendered', function (): void {
    $response = $this->get('/register');

    $response->assertOk()
        ->assertSee('Registrácia záujemcu')
        ->assertSee('name="date_of_birth"', false)
        ->assertSee('max="2011-11-01"', false)
        ->assertSee('name="street_address"', false)
        ->assertSee('name="terms"', false)
        ->assertSee('name="gdpr_consent"', false)
        ->assertDontSee('name="id_number"', false)
        ->assertSee('name="guardian_first_name"', false)
        ->assertSee('x-show="needsGuardian"', false)
        ->assertViewHas('adultBirthDate', '2008-11-01')
        ->assertViewHas('currentDate', '2026-11-01');
});

test('new users can register with the agreed user schema', function (): void {
    $response = $this->post('/register', array_replace($this->registrationData, [
        'role_id' => 2,
        'is_active' => false,
        'password_hash' => Hash::make('forged-password'),
    ]));

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::sole();

    expect($user->first_name)->toBe('Test')
        ->and($user->last_name)->toBe('User')
        ->and($user->name)->toBe('Test User')
        ->and($user->phone)->toBe('+421 900 123 456')
        ->and($user->street_address)->toBe('J. Herdu 1')
        ->and($user->city)->toBe('Trnava')
        ->and($user->postal_code)->toBe('917 01')
        ->and($user->date_of_birth->toDateString())->toBe('2000-01-01')
        ->and($user->role_id)->toBe(1)
        ->and($user->is_active)->toBeTrue()
        ->and($user->email_verified_at)->toBeNull()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and($user->toArray())->not->toHaveKey('password');

    Notification::assertSentToOnce($user, VerifyEmail::class);

    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    $this->get(route('verification.notice'))->assertOk()->assertViewIs('auth.verify-email');
    $this->get(route('admin.dashboard'))->assertRedirect(route('verification.notice'));

    $notification = Notification::sent($user, VerifyEmail::class)->sole();
    $verificationUrl = $notification->toMail($user)->actionUrl;

    $this->get($verificationUrl)->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    $this->get(route('dashboard'))->assertOk()->assertViewIs('dashboard');
    $this->get(route('admin.dashboard'))->assertForbidden();
});

test('registration rejects invalid details', function (array $invalidFields, string $errorField): void {
    $response = $this->post('/register', array_replace($this->registrationData, $invalidFields));

    $response->assertSessionHasErrors($errorField);
    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
})->with([
    'missing first name' => [['first_name' => ''], 'first_name'],
    'missing last name' => [['last_name' => ''], 'last_name'],
    'long first name' => [['first_name' => str_repeat('a', 101)], 'first_name'],
    'long last name' => [['last_name' => str_repeat('a', 101)], 'last_name'],
    'invalid email' => [['email' => 'invalid'], 'email'],
    'missing email' => [['email' => ''], 'email'],
    'invalid phone' => [['phone' => 'not a phone'], 'phone'],
    'short phone' => [['phone' => '12345'], 'phone'],
    'long phone' => [['phone' => str_repeat('1', 31)], 'phone'],
    'missing street' => [['street_address' => ''], 'street_address'],
    'long street' => [['street_address' => str_repeat('a', 256)], 'street_address'],
    'missing city' => [['city' => ''], 'city'],
    'long city' => [['city' => str_repeat('a', 101)], 'city'],
    'missing postal code' => [['postal_code' => ''], 'postal_code'],
    'long postal code' => [['postal_code' => str_repeat('1', 21)], 'postal_code'],
    'missing birth date' => [['date_of_birth' => ''], 'date_of_birth'],
    'invalid birth date' => [['date_of_birth' => 'not a date'], 'date_of_birth'],
    'impossible birth date' => [['date_of_birth' => '2000-02-31'], 'date_of_birth'],
    'birth date outside MySQL range' => [['date_of_birth' => '0999-12-31'], 'date_of_birth'],
    'future birth date' => [['date_of_birth' => '2026-11-02'], 'date_of_birth'],
    'younger than fifteen' => [['date_of_birth' => '2012-11-01'], 'date_of_birth'],
    'fifteenth birthday tomorrow' => [['date_of_birth' => '2011-11-02'], 'date_of_birth'],
    'fifteenth birthday next month' => [['date_of_birth' => '2011-12-01'], 'date_of_birth'],
    'birth date today' => [['date_of_birth' => '2026-11-01'], 'date_of_birth'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'different confirmation' => [['password_confirmation' => 'different'], 'password'],
    'unaccepted terms' => [['terms' => '0'], 'terms'],
    'unaccepted data processing' => [['gdpr_consent' => '0'], 'gdpr_consent'],
]);

test('an email address cannot be registered twice', function (): void {
    $user = User::factory()->create();

    $response = $this->post('/register', array_replace($this->registrationData, [
        'first_name' => 'Another',
        'email' => $user->email,
    ]));

    $response->assertSessionHasErrors('email');
    $this->assertGuest();
    $this->assertDatabaseCount('users', 1);
});

test('registration accepts customers who have reached their fifteenth birthday', function (string $birthDate): void {
    $response = $this->post('/register', array_replace($this->registrationData, $this->guardianData, [
        'date_of_birth' => $birthDate,
    ]));

    $response->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();
    expect(User::sole()->date_of_birth->toDateString())->toBe($birthDate);
})->with([
    'eighteenth birthday today' => '2008-11-01',
    'older adult' => '1990-06-15',
    'seventeen years old' => '2009-11-01',
    'eighteenth birthday tomorrow' => '2008-11-02',
    'fifteen years old' => '2011-11-01',
    'fifteenth birthday yesterday' => '2011-10-31',
    'eighteenth birthday yesterday' => '2008-10-31',
]);

test('minimum registration age handles leap days without advancing birthdays', function (string $currentDate, string $birthDate, bool $canRegister): void {
    $this->travelTo(now()->setDateFrom($currentDate));

    $response = $this->post('/register', array_replace($this->registrationData, $this->guardianData, [
        'date_of_birth' => $birthDate,
    ]));

    if ($canRegister) {
        $response->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
        expect(User::sole()->date_of_birth->toDateString())->toBe($birthDate);
    } else {
        $response->assertSessionHasErrors('date_of_birth');
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }
})->with([
    'February birthday reached on leap day' => ['2028-02-29', '2013-02-28', true],
    'March birthday not reached on leap day' => ['2028-02-29', '2013-03-01', false],
    'March birthday reached on March first' => ['2028-03-01', '2013-03-01', true],
    'leap-day birthday not reached on February twenty-eighth' => ['2027-02-28', '2012-02-29', false],
    'leap-day birthday reached on March first' => ['2027-03-01', '2012-02-29', true],
]);

test('registration provides matching calendar cutoffs to the form on leap day', function (): void {
    $this->travelTo(now()->setDate(2028, 2, 29));

    $this->get('/register')->assertOk()
        ->assertViewHas('latestBirthDate', '2013-02-28')
        ->assertViewHas('adultBirthDate', '2010-02-28')
        ->assertViewHas('currentDate', '2028-02-29')
        ->assertSee('max="2013-02-28"', false);
});

test('registration allows an omitted phone number', function (?string $phone): void {
    $response = $this->post('/register', array_replace($this->registrationData, [
        'phone' => $phone,
    ]));

    $response->assertSessionHasNoErrors();
    $this->assertAuthenticated();
    expect(User::sole()->phone)->toBeNull();
})->with([null, '']);

test('registration requires both consent fields in direct requests', function (string $field): void {
    $data = $this->registrationData;
    unset($data[$field]);

    $this->post('/register', $data)->assertSessionHasErrors($field);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
})->with(['terms', 'gdpr_consent']);

test('registration keeps entered details after validation fails without flashing passwords', function (): void {
    $response = $this->from('/register')->post('/register', array_replace($this->registrationData, [
        'password_confirmation' => 'different',
    ]));

    $response->assertRedirect('/register')
        ->assertSessionHasErrors('password')
        ->assertSessionHasInput('city', 'Trnava')
        ->assertSessionHasInput('date_of_birth', '2000-01-01')
        ->assertSessionHasInput('terms', '1')
        ->assertSessionMissing('_old_input.password')
        ->assertSessionMissing('_old_input.password_confirmation');

    $this->get('/register')->assertOk()
        ->assertSee('value="Trnava"', false)
        ->assertSee('value="2000-01-01"', false);
});

test('existing accounts can have no birth date', function (): void {
    $user = User::factory()->create();

    expect($user->fresh()->date_of_birth)->toBeNull();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
});

test('minor registration requires all guardian fields even when browser validation is bypassed', function (string $birthDate): void {
    $this->post('/register', array_replace($this->registrationData, [
        'date_of_birth' => $birthDate,
    ]))->assertSessionHasErrors(['guardian_first_name', 'guardian_last_name', 'guardian_email', 'guardian_date_of_birth']);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    Notification::assertNothingSent();
})->with([
    'fifteenth birthday today' => '2011-11-01',
    'seventeen years old' => '2009-11-01',
    'eighteenth birthday tomorrow' => '2008-11-02',
]);

test('minor registration rejects invalid guardian details', function (array $invalidFields, string $errorField): void {
    $this->post('/register', array_replace($this->registrationData, $this->guardianData, [
        'date_of_birth' => '2011-11-01',
    ], $invalidFields))->assertSessionHasErrors($errorField);

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    Notification::assertNothingSent();
})->with([
    'empty first name' => [['guardian_first_name' => ''], 'guardian_first_name'],
    'empty last name' => [['guardian_last_name' => ''], 'guardian_last_name'],
    'empty email' => [['guardian_email' => ''], 'guardian_email'],
    'first name is not text' => [['guardian_first_name' => ['Guardian']], 'guardian_first_name'],
    'last name is not text' => [['guardian_last_name' => ['Person']], 'guardian_last_name'],
    'ID number is not text' => [['guardian_id_number' => ['AA123456']], 'guardian_id_number'],
    'long first name' => [['guardian_first_name' => str_repeat('a', 101)], 'guardian_first_name'],
    'long last name' => [['guardian_last_name' => str_repeat('a', 101)], 'guardian_last_name'],
    'long ID number' => [['guardian_id_number' => 'AA1234567'], 'guardian_id_number'],
    'short ID number' => [['guardian_id_number' => 'AA12345'], 'guardian_id_number'],
    'ID number contains only letters' => [['guardian_id_number' => 'ABCDEFGH'], 'guardian_id_number'],
    'ID number contains only digits' => [['guardian_id_number' => '12345678'], 'guardian_id_number'],
    'ID number contains punctuation' => [['guardian_id_number' => 'AB1234-5'], 'guardian_id_number'],
    'ID number contains a space' => [['guardian_id_number' => 'AB12 345'], 'guardian_id_number'],
    'ID number contains a non-ASCII letter' => [['guardian_id_number' => 'ÁB123456'], 'guardian_id_number'],
    'ID number mixes letters and digits' => [['guardian_id_number' => 'a1B2c3D4'], 'guardian_id_number'],
    'ID number starts with three letters' => [['guardian_id_number' => 'ABC12345'], 'guardian_id_number'],
    'ID number starts with one letter' => [['guardian_id_number' => 'A1234567'], 'guardian_id_number'],
    'ID number ends with letters' => [['guardian_id_number' => '123456AB'], 'guardian_id_number'],
]);

test('adult registration excludes irrelevant guardian fields', function (string $birthDate): void {
    $response = $this->post('/register', array_replace($this->registrationData, [
        'date_of_birth' => $birthDate,
        'guardian_first_name' => ['ignored'],
        'guardian_last_name' => ['ignored'],
        'guardian_id_number' => ['ignored'],
    ]));

    $response->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticated();
    expect(User::sole()->date_of_birth->toDateString())->toBe($birthDate);
})->with([
    'eighteenth birthday today' => '2008-11-01',
    'older adult' => '1990-06-15',
]);

test('guardian details survive validation errors for a minor registration', function (): void {
    $this->from('/register')->post('/register', array_replace($this->registrationData, $this->guardianData, [
        'date_of_birth' => '2011-11-01',
        'password_confirmation' => 'different',
    ]))->assertSessionHasErrors('password')
        ->assertSessionHasInput('guardian_first_name', 'Guardian')
        ->assertSessionHasInput('guardian_last_name', 'Person')
        ->assertSessionHasInput('guardian_email', 'guardian@example.com');

    $this->get('/register')->assertOk()
        ->assertSee('value="Guardian"', false)
        ->assertSee('value="Person"', false)
        ->assertSee('value="guardian@example.com"', false);
});

test('guardian requirement handles a leap-day eighteenth birthday', function (string $currentDate, bool $requiresGuardian): void {
    $this->travelTo(now()->setDateFrom($currentDate));

    $response = $this->post('/register', array_replace($this->registrationData, [
        'date_of_birth' => '2008-02-29',
    ]));

    if ($requiresGuardian) {
        $response->assertSessionHasErrors(['guardian_first_name', 'guardian_last_name', 'guardian_email', 'guardian_date_of_birth']);
        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    } else {
        $response->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));
        $this->assertAuthenticated();
    }
})->with([
    'before eighteenth birthday' => ['2026-02-28', true],
    'eighteenth birthday reached' => ['2026-03-01', false],
]);

test('registration accepts ID numbers with two letters followed by six digits', function (string $idNumber): void {
    $this->post('/register', array_replace($this->registrationData, $this->guardianData, [
        'date_of_birth' => '2011-11-01',
        'id_number' => $idNumber,
        'guardian_id_number' => $idNumber,
    ]))->assertSessionHasNoErrors()->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    expect(User::sole()->id_number)->toBe($idNumber)
        ->and(UserGuardian::sole()->id_number)->toBe($idNumber);
})->with(['AA123456', 'ab123456', 'aB987654']);

test('registration rejects an invalid customer ID number when provided', function (mixed $idNumber): void {
    $this->post('/register', array_replace($this->registrationData, [
        'id_number' => $idNumber,
    ]))->assertSessionHasErrors('id_number');

    $this->assertGuest();
    $this->assertDatabaseCount('users', 0);
    Notification::assertNothingSent();
})->with([
    'too short' => ['AA12345'],
    'too long' => ['AA1234567'],
    'letters only' => ['ABCDEFGH'],
    'digits only' => ['12345678'],
    'punctuation' => ['AB1234-5'],
    'internal space' => ['AB12 345'],
    'non-ASCII letter' => ['ÁB123456'],
    'mixed letters and digits' => ['a1B2c3D4'],
    'three initial letters' => ['ABC12345'],
    'one initial letter' => ['A1234567'],
    'letters at the end' => ['123456AB'],
    'array instead of text' => [['AA123456']],
]);

test('customer ID number remains optional', function (?string $idNumber): void {
    $this->post('/register', array_replace($this->registrationData, [
        'id_number' => $idNumber,
    ]))->assertSessionHasNoErrors();

    $this->assertAuthenticated();
})->with([null, '']);
