<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite' && ! Schema::hasTable('user_guardians')) {
            return;
        }

        if (DB::table('user_guardians')->whereNull('date_of_birth')->exists()) {
            throw new RuntimeException('Fill the missing guardian birth dates before requiring this column. No replacement dates are generated.');
        }

        Schema::table('user_guardians', function (Blueprint $table): void {
            $table->date('date_of_birth')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite' && ! Schema::hasTable('user_guardians')) {
            return;
        }

        Schema::table('user_guardians', function (Blueprint $table): void {
            $table->date('date_of_birth')->nullable()->change();
        });
    }
};
