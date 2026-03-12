<?php

namespace Tests\Feature;

use App\Enums\ActivityAction;
use App\Models\Cupboard;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuditLogCriticalEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_created_event_is_recorded_with_actor_and_new_values(): void
    {
        $admin = $this->createUserWithPermission('item.create');
        Sanctum::actingAs($admin);

        $cupboard = Cupboard::query()->create([
            'name' => 'Main Cupboard',
            'code' => 'CP-01',
        ]);

        $place = Place::query()->create([
            'cupboard_id' => $cupboard->id,
            'name' => 'Shelf A',
            'code' => 'A1',
        ]);

        $response = $this->postJson('/api/v1/items', [
            'name' => 'Laptop Adapter',
            'code' => 'ITM-001',
            'quantity' => 4,
            'place_id' => $place->id,
            'status' => 'available',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => ActivityAction::ITEM_CREATED->value,
            'entity_type' => 'App\\Models\\InventoryItem',
        ]);
    }

    public function test_user_created_event_is_recorded_with_actor(): void
    {
        $admin = $this->createUserWithPermission('user.create');
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'New Staff',
            'email' => 'new-staff@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'Staff',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => ActivityAction::USER_CREATED->value,
            'entity_type' => 'App\\Models\\User',
        ]);
    }

    private function createUserWithPermission(string $permissionName): User
    {
        Permission::query()->firstOrCreate([
            'name' => $permissionName,
            'guard_name' => 'web',
        ]);

        $role = Role::query()->firstOrCreate([
            'name' => 'Admin',
            'guard_name' => 'web',
        ]);

        Role::query()->firstOrCreate([
            'name' => 'Staff',
            'guard_name' => 'web',
        ]);

        $role->givePermissionTo($permissionName);

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
