<?php

namespace Database\Seeders;

use App\Models\UserGuardian;
use Illuminate\Database\Seeder;

class UserGuardianSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserGuardian::factory()->create();
    }
}
