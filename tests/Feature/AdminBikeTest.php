<?php

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    if (! Schema::hasTable('bikes')) {
        Schema::create('bikes', function (Blueprint $table): void {
            $table->id();
            $table->string('inventory_number', 50)->unique();
            $table->string('serial_number', 100)->nullable()->unique();
            $table->string('model', 150)->nullable();
            $table->string('status', 20)->default('ready');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
});

function insertAdminTestBike(array $overrides = []): int
{
    return DB::table('bikes')->insertGetId(array_merge([
        'inventory_number' => 'B-001',
        'serial_number' => 'SN-CB-0001',
        'model' => 'City Cruiser',
        'status' => 'ready',
        'notes' => null,
        'created_at' => now(),
        'updated_at' => now(),
    ], $overrides));
}

test('guests are redirected away from the bicycle administration', function (): void {
    $this->get(route('admin.bikes.index'))->assertRedirect(route('login'));
});

test('customers cannot open the bicycle administration', function (): void {
    $customer = User::factory()->create();

    $this->actingAs($customer)
        ->get(route('admin.bikes.index'))
        ->assertForbidden();
});

test('administrators can see bicycles and their status', function (): void {
    $admin = User::factory()->admin()->create();

    insertAdminTestBike();
    insertAdminTestBike([
        'inventory_number' => 'B-002',
        'serial_number' => 'SN-CB-0002',
        'model' => 'Mountain Pro',
        'status' => 'maintenance',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bikes.index'))
        ->assertOk()
        ->assertSee('B-001')
        ->assertSee('B-002')
        ->assertSee('City Cruiser')
        ->assertSee('Mountain Pro')
        ->assertSee('Pripravený')
        ->assertSee('V servise');
});

test('administrators can search bicycles by id, model and status', function (): void {
    $admin = User::factory()->admin()->create();

    $firstBikeId = insertAdminTestBike([
        'inventory_number' => 'ALPHA',
        'serial_number' => 'SN-ALPHA',
        'model' => 'Road Runner',
    ]);

    insertAdminTestBike([
        'inventory_number' => 'BETA',
        'serial_number' => 'SN-BETA',
        'model' => 'Trail Rider',
        'status' => 'maintenance',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.bikes.index', ['q' => (string) $firstBikeId]))
        ->assertOk()
        ->assertSee('ALPHA')
        ->assertDontSee('BETA');

    $this->get(route('admin.bikes.index', ['q' => 'Trail']))
        ->assertOk()
        ->assertSee('BETA')
        ->assertDontSee('ALPHA');

    $this->get(route('admin.bikes.index', ['status' => 'maintenance']))
        ->assertOk()
        ->assertSee('BETA')
        ->assertDontSee('ALPHA');
});

test('administrators can choose a page size', function (): void {
    $admin = User::factory()->admin()->create();

    foreach (range(1, 12) as $number) {
        insertAdminTestBike([
            'inventory_number' => 'B-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT),
            'serial_number' => 'SN-'.$number,
        ]);
    }

    $this->actingAs($admin)
        ->get(route('admin.bikes.index', ['per_page' => 20]))
        ->assertOk()
        ->assertViewHas('bikes', fn ($bikes): bool => $bikes->perPage() === 20 && $bikes->total() === 12);
});
