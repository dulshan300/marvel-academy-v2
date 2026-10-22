<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_registration_page_renders_for_guests(): void
    {
        $this->get(route('register'))->assertOk()->assertSee('Create your parent account');
    }

    public function test_valid_details_create_a_signed_in_parent_account(): void
    {
        Event::fake([Registered::class]);

        Livewire::test('pages::auth.register')
            ->set('name', 'Priya Sharma')
            ->set('email', 'Priya@Example.co.uk')
            ->set('phone', '07700 900456')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->set('terms', true)
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard', absolute: false));

        $user = User::where('email', 'priya@example.co.uk')->sole();

        $this->assertSame(Role::Parent, $user->primaryRole());
        $this->assertAuthenticatedAs($user);
        Event::assertDispatched(Registered::class);
    }

    public function test_selected_plan_is_remembered_for_checkout(): void
    {
        $plan = Plan::factory()->create(['slug' => 'achiever']);

        Livewire::withQueryParams(['plan' => $plan->slug])
            ->test('pages::auth.register')
            ->set('name', 'Priya Sharma')
            ->set('email', 'priya@example.co.uk')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->set('terms', true)
            ->call('register');

        $this->assertSame('achiever', session('checkout.plan'));
    }

    public function test_unknown_plan_is_not_remembered(): void
    {
        Livewire::withQueryParams(['plan' => 'made-up'])
            ->test('pages::auth.register')
            ->set('name', 'Priya Sharma')
            ->set('email', 'priya@example.co.uk')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->set('terms', true)
            ->call('register');

        $this->assertNull(session('checkout.plan'));
    }

    public function test_email_already_in_use_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.co.uk']);

        Livewire::test('pages::auth.register')
            ->set('name', 'Someone')
            ->set('email', 'TAKEN@example.co.uk')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->set('terms', true)
            ->call('register')
            ->assertHasErrors(['email' => 'unique']);
    }

    public function test_terms_must_be_accepted(): void
    {
        Livewire::test('pages::auth.register')
            ->set('name', 'Priya Sharma')
            ->set('email', 'priya@example.co.uk')
            ->set('password', 'secret123')
            ->set('password_confirmation', 'secret123')
            ->call('register')
            ->assertHasErrors(['terms' => 'accepted']);

        $this->assertGuest();
    }

    public function test_password_must_be_confirmed_and_contain_letters_and_numbers(): void
    {
        Livewire::test('pages::auth.register')
            ->set('password', 'onlyletters')
            ->set('password_confirmation', 'different1')
            ->call('register')
            ->assertHasErrors('password');
    }
}
