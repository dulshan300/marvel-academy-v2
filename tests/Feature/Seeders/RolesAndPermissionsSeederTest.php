<?php

namespace Tests\Feature\Seeders;

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolesAndPermissionsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_every_portal_role(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertEqualsCanonicalizing(
            array_map(fn (RoleEnum $role) => $role->value, RoleEnum::cases()),
            Role::pluck('name')->all(),
        );
    }

    public function test_grants_every_staff_permission_to_admins_only(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = Role::findByName(RoleEnum::Admin->value);

        foreach (Permission::cases() as $permission) {
            $this->assertTrue($admin->hasPermissionTo($permission->value), "Admin lacks [{$permission->value}].");
        }

        $this->assertCount(0, Role::findByName(RoleEnum::Parent->value)->permissions);
    }

    public function test_running_twice_does_not_duplicate_roles(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->assertSame(count(RoleEnum::cases()), Role::count());
    }
}
