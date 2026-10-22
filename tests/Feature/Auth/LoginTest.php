<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LoginTest extends TestCase
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

    public function test_login_page_renders_for_guests(): void
    {
        $this->get(route('login'))->assertOk()->assertSee('Welcome back');
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $this->actingAs(User::factory()->parent()->create())
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_valid_credentials_sign_in_and_record_last_login(): void
    {
        $user = User::factory()->parent()->create();

        Livewire::test('pages::auth.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_wrong_password_is_rejected(): void
    {
        $user = User::factory()->parent()->create();

        Livewire::test('pages::auth.login')
            ->set('email', $user->email)
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_deactivated_account_cannot_sign_in(): void
    {
        $user = User::factory()->parent()->inactive()->create();

        Livewire::test('pages::auth.login')
            ->set('email', $user->email)
            ->set('password', 'password')
            ->call('login')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_sixth_failed_attempt_is_throttled_even_with_correct_password(): void
    {
        $user = User::factory()->parent()->create();
        $component = Livewire::test('pages::auth.login')->set('email', $user->email);

        foreach (range(1, 5) as $attempt) {
            $component->set('password', 'wrong-password')->call('login');
        }

        $component->set('password', 'password')->call('login')->assertHasErrors('email');

        $this->assertGuest();
    }

    #[DataProvider('roles')]
    public function test_dashboard_redirects_each_role_to_its_portal(Role $role): void
    {
        $user = User::factory()->withRole($role)->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route($role->dashboardRoute()));
    }

    public function test_dashboard_forbids_users_without_a_role(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertForbidden();
    }

    public function test_portals_forbid_other_roles(): void
    {
        $this->actingAs(User::factory()->parent()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();

        $this->actingAs(User::factory()->student()->create())
            ->get(route('parent.dashboard'))
            ->assertForbidden();
    }

    public function test_guests_are_redirected_to_login_from_portals(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    }

    public function test_deactivated_user_with_an_open_session_is_signed_out(): void
    {
        $user = User::factory()->parent()->inactive()->create();

        $this->actingAs($user)
            ->get(route('parent.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_ends_the_session(): void
    {
        $this->actingAs(User::factory()->parent()->create())
            ->post(route('logout'))
            ->assertRedirect(route('home'));

        $this->assertGuest();
    }
}
