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
        if (DB::getDriverName() === 'sqlite') {
            $this->createSqliteAuthTables();
        } elseif (! Schema::hasTable('users') || ! Schema::hasTable('user_roles')) {
            throw new RuntimeException(
                'Import the business schema supplied by the database team before running migrations on MySQL.',
            );
        }

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {

        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('users');
            Schema::dropIfExists('user_roles');
        }
    }

    /**
     * Bootstrap SQLite authentication tests and previews; MySQL uses the imported SQL schema.
     */
    private function createSqliteAuthTables(): void
    {
        Schema::create('user_roles', function (Blueprint $table): void {
            $table->smallIncrements('id');
            $table->string('code', 30)->unique();
            $table->string('name', 100);
        });

        DB::table('user_roles')->insert([
            ['id' => 1, 'code' => 'customer', 'name' => 'Customer'],
            ['id' => 2, 'code' => 'admin', 'name' => 'Administrator'],
        ]);

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('email', 254)->unique();
            $table->string('password_hash');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('phone', 30)->nullable();
            $table->string('street_address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->unsignedSmallInteger('role_id')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
            $table->foreign('role_id')->references('id')->on('user_roles');
        });
    }
};
