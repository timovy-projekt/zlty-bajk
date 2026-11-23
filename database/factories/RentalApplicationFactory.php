<?php

namespace Database\Factories;

use App\Models\RentalApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RentalApplication>
 */
class RentalApplicationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'status' => RentalApplication::STATUS_SUBMITTED,
            'submitted_at' => now(),
            'reviewed_at' => null,
            'reviewed_by' => null,
            'notes' => null,
        ];
    }
}
