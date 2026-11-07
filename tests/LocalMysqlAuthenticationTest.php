<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Opt-in MySQL checks: php artisan test --compact tests/LocalMysqlAuthenticationTest.php.
 * Kept outside Feature so RefreshDatabase never resets the development database.
 * Every test rolls back its own transaction and uses fake mail notifications.
 */
uses(TestCase::class);

beforeEach(function (): void {
    $configuration = Dotenv\Dotenv::createArrayBacked(base_path())->load();

    if (($configuration['APP_ENV'] ?? '') !== 'local'
        || ($configuration['DB_CONNECTION'] ?? '') !== 'mysql'
        || ($configuration['DB_HOST'] ?? '') !== '127.0.0.1'
        || ($configuration['DB_PORT'] ?? '') !== '3306'
        || ($configuration['DB_DATABASE'] ?? '') !== 'bajkuj_auth_local'
        || ! empty($configuration['DB_URL'])) {
        throw new RuntimeException('These tests require the isolated local bajkuj_auth_local database.');
    }

    config([
        'database.default' => 'mysql',
        'database.connections.mysql.url' => null,
        'database.connections.mysql.host' => '127.0.0.1',
        'database.connections.mysql.port' => '3306',
        'database.connections.mysql.database' => 'bajkuj_auth_local',
        'database.connections.mysql.username' => $configuration['DB_USERNAME'] ?? '',
        'database.connections.mysql.password' => $configuration['DB_PASSWORD'] ?? '',
        'mail.default' => 'array',
        'session.driver' => 'array',
        'cache.default' => 'array',
        'queue.default' => 'sync',
    ]);

    DB::purge('mysql');
    $this->localMysqlConnection = DB::connection('mysql');
    $this->localMysqlConnection->beginTransaction();

    $birthDateColumn = $this->localMysqlConnection->selectOne(
        "SELECT IS_NULLABLE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_guardians' AND COLUMN_NAME = 'date_of_birth'",
    );

    if ($birthDateColumn?->IS_NULLABLE !== 'NO') {
        throw new RuntimeException('Apply the guardian birth-date migration before running the local MySQL checks.');
    }

    Notification::fake();
    $this->travelTo(now()->setDate(2026, 11, 7)->setTime(12, 0));
});

afterEach(function (): void {
    if (isset($this->localMysqlConnection)) {
        while ($this->localMysqlConnection->transactionLevel() > 0) {
            $this->localMysqlConnection->rollBack();
        }

        DB::purge('mysql');
    }
});

test('registration verification login and password reset work on local MySQL', function (string $birthDate, bool $hasGuardian, bool $sameAddress): void {
    $email = 'bajkuj-mysql-'.bin2hex(random_bytes(8)).'@example.com';
    $data = [
        'first_name' => 'Local',
        'last_name' => 'Customer',
        'email' => $email,
        'phone' => '+421 900 123 456',
        'street_address' => 'Customer Street 1',
        'city' => 'Trnava',
        'postal_code' => '917 01',
        'date_of_birth' => $birthDate,
        'password' => 'test-password',
        'password_confirmation' => 'test-password',
        'terms' => '1',
        'gdpr_consent' => '1',
    ];

    if ($hasGuardian) {
        $data = [...$data,
            'guardian_first_name' => 'Local',
            'guardian_last_name' => 'Guardian',
            'guardian_email' => 'guardian-'.$email,
            'guardian_date_of_birth' => '1980-07-08',
            'guardian_same_address' => $sameAddress ? '1' : '0',
            'guardian_street_address' => 'Guardian Street 2',
            'guardian_city' => 'Bratislava',
            'guardian_postal_code' => '811 01',
        ];
    }

    $this->post(route('register'), $data)->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', $email)->sole();
    $this->assertAuthenticatedAs($user);
    expect(Hash::check('test-password', $user->password))->toBeTrue()
        ->and($user->id_number)->toBeNull();

    if ($hasGuardian) {
        expect($user->guardian->user_id)->toBe($user->id)
            ->and($user->guardian->date_of_birth->toDateString())->toBe('1980-07-08')
            ->and($user->guardian->city)->toBe($sameAddress ? 'Trnava' : 'Bratislava')
            ->and($user->guardian->id_number)->toBeNull();
    } else {
        expect($user->guardian)->toBeNull();
    }

    $this->get(route('dashboard'))->assertRedirect(route('verification.notice'));
    Notification::assertSentToOnce($user, VerifyEmail::class);
    $verification = Notification::sent($user, VerifyEmail::class)->sole();
    $this->get($verification->toMail($user)->actionUrl)->assertRedirect();
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();

    $this->get(route('dashboard'))->assertOk()->assertSee($email);
    $this->post(route('logout'))->assertRedirect('/');
    $this->assertGuest();

    $this->post(route('login'), ['email' => $email, 'password' => 'test-password'])
        ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    $this->post(route('logout'))->assertRedirect('/');

    $this->post(route('password.email'), ['email' => $email])->assertSessionHasNoErrors();
    Notification::assertSentToOnce($user, ResetPassword::class);
    $reset = Notification::sent($user, ResetPassword::class)->sole();

    $this->post(route('password.store'), [
        'token' => $reset->token,
        'email' => $email,
        'password' => 'replacement-password',
        'password_confirmation' => 'replacement-password',
    ])->assertSessionHasNoErrors()->assertRedirect(route('login'));

    $this->post(route('login'), ['email' => $email, 'password' => 'test-password'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post(route('login'), ['email' => $email, 'password' => 'replacement-password'])
        ->assertSessionHasNoErrors()->assertRedirect(route('dashboard'));
    $this->assertAuthenticatedAs($user);
    expect(Hash::check('replacement-password', $user->fresh()->password))->toBeTrue();
})->with([
    'adult customer' => ['2000-01-01', false, false],
    'minor with separate guardian address' => ['2010-11-07', true, false],
    'minor with shared guardian address' => ['2010-11-07', true, true],
]);
