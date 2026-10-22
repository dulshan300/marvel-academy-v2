<?php

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function adminPages(): array
    {
        return [
            'dashboard' => ['admin.dashboard'],
            'courses' => ['admin.courses.index'],
            'new course' => ['admin.courses.create'],
            'sessions' => ['admin.sessions.index'],
            'new session' => ['admin.sessions.create'],
        ];
    }

    #[DataProvider('adminPages')]
    public function test_admin_can_open_admin_page(string $routeName): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route($routeName))
            ->assertOk();
    }

    #[DataProvider('adminPages')]
    public function test_parents_are_forbidden_from_admin_pages(string $routeName): void
    {
        $this->actingAs(User::factory()->parent()->create())
            ->get(route($routeName))
            ->assertForbidden();
    }

    public function test_staff_role_only_reaches_the_modules_it_is_granted(): void
    {
        $contentEditor = Role::create(['name' => 'course editor', 'guard_name' => 'web'])
            ->givePermissionTo(Permission::ManageCourses->value);
        $staff = User::factory()->create();
        $staff->assignRole($contentEditor);

        $this->actingAs($staff)->get(route('dashboard'))->assertRedirect(route('admin.dashboard'));
        $this->actingAs($staff)->get(route('admin.courses.index'))->assertOk();
        $this->actingAs($staff)->get(route('admin.sessions.index'))->assertForbidden();
    }
}
