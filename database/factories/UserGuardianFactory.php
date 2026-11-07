<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserGuardian;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserGuardian>
 */
class UserGuardianFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->state(['date_of_birth' => today()->subYearsNoOverflow(16)]),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->safeEmail(),
            'phone' => null,
            'street_address' => null,
            'city' => null,
            'postal_code' => null,
            'date_of_birth' => today()->subYearsNoOverflow(40),
            'id_number' => null,
        ];
    }
}
