<?php

namespace Tests\Feature\Models;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{Role}>
     */
    public static function roles(): array
    {
        return collect(Role::cases())
            ->mapWithKeys(fn (Role $role): array => [$role->value => [$role]])
            ->all();
    }

    #[DataProvider('roles')]
    public function test_primary_role_returns_the_assigned_portal_role(Role $role): void
    {
        $user = User::factory()->withRole($role)->create();

        $this->assertSame($role, $user->primaryRole());
    }

    public function test_primary_role_is_null_without_a_role(): void
    {
        $user = User::factory()->create();

        $this->assertNull($user->primaryRole());
    }

    public function test_new_users_get_a_ulid_used_as_the_route_key(): void
    {
        $user = User::factory()->create();

        $this->assertTrue(str($user->ulid)->isUlid());
        $this->assertSame($user->ulid, $user->getRouteKey());
        $this->assertIsInt($user->getKey());
    }
}
