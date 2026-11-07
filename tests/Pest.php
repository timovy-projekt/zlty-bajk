<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->beforeEach(function (): void {
    if (config('database.default') !== 'sqlite') {
        return;
    }

    Schema::table('users', function (Blueprint $table): void {
        $table->string('id_number', 8)->nullable();
    });

    Schema::create('user_guardians', function (Blueprint $table): void {
        $table->id();
        $table->foreignId('user_id')->unique()->constrained('users')->restrictOnDelete()->restrictOnUpdate();
        $table->string('first_name', 100);
        $table->string('last_name', 100);
        $table->string('email', 254);
        $table->string('phone', 30)->nullable();
        $table->string('street_address')->nullable();
        $table->string('city', 100)->nullable();
        $table->string('postal_code', 20)->nullable();
        $table->date('date_of_birth');
        $table->string('id_number', 8)->nullable();
        $table->timestamps();
    });
})->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}
