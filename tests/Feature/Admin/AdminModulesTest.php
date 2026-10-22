<?php

namespace Tests\Feature\Admin;

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Models\Child;
use App\Models\ContactMessage;
use App\Models\Coupon;
use App\Models\Course;
use App\Models\NewsletterSubscriber;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function modulePages(): array
    {
        return [
            'families' => ['admin.families.index'],
            'teachers' => ['admin.teachers.index'],
            'new teacher' => ['admin.teachers.create'],
            'payments' => ['admin.payments.index'],
            'content' => ['admin.content.index'],
            'roles' => ['admin.roles.index'],
        ];
    }

    #[DataProvider('modulePages')]
    public function test_admin_can_open_module(string $routeName): void
    {
        $this->actingAs($this->admin)->get(route($routeName))->assertOk();
    }

    #[DataProvider('modulePages')]
    public function test_teachers_cannot_open_module(string $routeName): void
    {
        $this->actingAs(User::factory()->teacher()->create())->get(route($routeName))->assertForbidden();
    }

    public function test_families_search_finds_parents_by_child_name(): void
    {
        $child = Child::factory()->create(['first_name' => 'Zainab', 'last_name' => 'Khan']);
        $other = Child::factory()->create(['first_name' => 'Oliver']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.families.index')
            ->set('search', 'zainab')
            ->assertSee($child->parent->email)
            ->assertDontSee($other->parent->email);
    }

    public function test_admin_can_deactivate_a_parent_and_their_childs_login(): void
    {
        $child = Child::factory()->withStudentAccount()->create();
        $parent = $child->parent;

        Livewire::actingAs($this->admin)
            ->test('pages::admin.families.show', ['parent' => $parent])
            ->call('toggleActive')
            ->call('toggleActive', $child->student_id);

        $this->assertFalse($parent->fresh()->is_active);
        $this->assertFalse($child->student->fresh()->is_active);
    }

    public function test_family_page_rejects_users_from_other_families(): void
    {
        $child = Child::factory()->withStudentAccount()->create();
        $stranger = User::factory()->student()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.families.show', ['parent' => $child->parent])
            ->call('toggleActive', $stranger->id)
            ->assertNotFound();

        $this->assertTrue($stranger->fresh()->is_active);
    }

    public function test_admin_creates_a_teacher_with_courses(): void
    {
        $course = Course::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.teachers.edit')
            ->set('name', 'Nalaka Jayantha')
            ->set('email', 'Nalaka@Example.co.uk')
            ->set('password', 'teacher123')
            ->set('headline', 'Mathematics tutor')
            ->set('courseIds', [(string) $course->id])
            ->call('save')
            ->assertHasNoErrors();

        $teacher = User::where('email', 'nalaka@example.co.uk')->sole();
        $this->assertSame(RoleEnum::Teacher, $teacher->primaryRole());
        $this->assertSame('Mathematics tutor', $teacher->teacherProfile->headline);
        $this->assertTrue($teacher->taughtCourses->first()->is($course));
    }

    public function test_admin_creates_a_percentage_coupon_with_an_uppercase_code(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.payments.index')
            ->set('code', 'sibling 10')
            ->set('name', 'Sibling discount')
            ->set('discountType', 'percent')
            ->set('discountValue', '10')
            ->set('duration', 'forever')
            ->call('createCoupon')
            ->assertHasNoErrors();

        $coupon = Coupon::sole();
        $this->assertSame('SIBLING10', $coupon->code);
        $this->assertSame(10, $coupon->percent_off);
        $this->assertNull($coupon->amount_off);
    }

    public function test_fixed_amount_coupon_is_stored_in_pence(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.payments.index')
            ->set('code', 'FIVEOFF')
            ->set('name', 'Five pounds off')
            ->set('discountType', 'amount')
            ->set('discountValue', '5.00')
            ->call('createCoupon')
            ->assertHasNoErrors();

        $this->assertSame(500, Coupon::sole()->amount_off);
    }

    public function test_repeating_coupon_requires_a_number_of_months(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.payments.index')
            ->set('code', 'TERM')
            ->set('name', 'Term offer')
            ->set('discountValue', '20')
            ->set('duration', 'repeating')
            ->call('createCoupon')
            ->assertHasErrors(['duration_in_months' => 'required']);
    }

    public function test_opening_an_enquiry_marks_it_read(): void
    {
        $message = ContactMessage::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.content.index')
            ->call('openMessage', $message->id)
            ->assertSee($message->message);

        $this->assertNotNull($message->fresh()->read_at);
    }

    public function test_hiding_a_testimonial_removes_it_from_the_home_page(): void
    {
        $testimonial = Testimonial::factory()->create(['content' => 'A wonderful experience for our family.']);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.content.index', ['tab' => 'testimonials'])
            ->call('toggleTestimonial', $testimonial->id, 'is_published');

        $this->get(route('home'))->assertDontSee('A wonderful experience for our family.');
    }

    public function test_only_known_testimonial_flags_can_be_toggled(): void
    {
        $testimonial = Testimonial::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.content.index')
            ->call('toggleTestimonial', $testimonial->id, 'rating')
            ->assertStatus(422);
    }

    public function test_newsletter_export_lists_active_subscribers_and_neutralises_formulas(): void
    {
        NewsletterSubscriber::factory()->create(['email' => 'keep@example.co.uk', 'name' => '=HYPERLINK("x")']);
        $gone = NewsletterSubscriber::factory()->create(['email' => 'gone@example.co.uk']);
        $gone->forceFill(['unsubscribed_at' => now()])->save();

        $csv = $this->actingAs($this->admin)->get(route('admin.newsletter.export'))->assertOk()->streamedContent();

        $this->assertStringContainsString('keep@example.co.uk', $csv);
        $this->assertStringNotContainsString('gone@example.co.uk', $csv);
        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_permission_toggle_grants_a_custom_role_access(): void
    {
        $role = Role::create(['name' => 'content editor', 'guard_name' => 'web']);
        $editor = User::factory()->create();
        $editor->assignRole($role);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.roles.index')
            ->call('togglePermission', $role->id, Permission::ManageContent->value);

        $this->actingAs($editor->fresh())->get(route('admin.content.index'))->assertOk();
    }

    public function test_admin_role_permissions_cannot_be_changed(): void
    {
        $adminRole = Role::findByName(RoleEnum::Admin->value);

        Livewire::actingAs($this->admin)
            ->test('pages::admin.roles.index')
            ->call('togglePermission', $adminRole->id, Permission::ManageRoles->value)
            ->assertForbidden();

        $this->assertTrue($adminRole->fresh()->hasPermissionTo(Permission::ManageRoles->value));
    }

    public function test_admin_cannot_remove_their_own_admin_role(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.roles.index')
            ->call('removeStaffRole', $this->admin->id, RoleEnum::Admin->value)
            ->assertForbidden();

        $this->assertTrue($this->admin->fresh()->isAdmin());
    }

    public function test_staff_account_cannot_be_given_a_portal_role(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.roles.index')
            ->set('staffName', 'Sneaky')
            ->set('staffEmail', 'sneaky@example.co.uk')
            ->set('staffPassword', 'password123')
            ->set('staffRole', RoleEnum::Parent->value)
            ->call('createStaff')
            ->assertHasErrors('staffRole');
    }

    public function test_portal_roles_cannot_be_deleted(): void
    {
        Livewire::actingAs($this->admin)
            ->test('pages::admin.roles.index')
            ->call('deleteRole', Role::findByName(RoleEnum::Teacher->value)->id)
            ->assertForbidden();
    }
}
