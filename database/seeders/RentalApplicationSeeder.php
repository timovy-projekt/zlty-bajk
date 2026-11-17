<?php

namespace Database\Seeders;

use App\Models\RentalApplication;
use Illuminate\Database\Seeder;

class RentalApplicationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        RentalApplication::factory()->create();
    }
}
