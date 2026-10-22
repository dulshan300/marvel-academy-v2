<?php

namespace Tests\Feature\Parent;

use App\Enums\EnrolmentStatus;
use App\Enums\KeyStage;
use App\Models\Child;
use App\Models\Course;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class EditChildTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_parent_can_update_their_childs_details_and_login(): void
    {
        $child = Child::factory()->withStudentAccount()->create(['year_group' => 8, 'key_stage' => KeyStage::Ks3]);

        Livewire::actingAs($child->parent)
            ->test('pages::parent.children.edit', ['child' => $child])
            ->assertSet('first_name', $child->first_name)
            ->set('first_name', 'Maya')
            ->set('last_name', 'Patel')
            ->set('year_group', 9)
            ->set('school_name', 'Riverside School')
            ->set('student_email', 'Maya.Patel@Example.com')
            ->set('student_password', 'newpass123')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('parent.dashboard'));

        $child->refresh()->load('student');
        $this->assertSame('Maya', $child->first_name);
        $this->assertSame(9, $child->year_group);
        $this->assertSame('Riverside School', $child->school_name);
        $this->assertSame('Maya Patel', $child->student->name);
        $this->assertSame('maya.patel@example.com', $child->student->email);
        $this->assertTrue(Hash::check('newpass123', $child->student->password));
    }

    public function test_blank_password_keeps_the_current_one(): void
    {
        $child = Child::factory()->withStudentAccount()->create();
        $hash = $child->student->password;

        Livewire::actingAs($child->parent)
            ->test('pages::parent.children.edit', ['child' => $child])
            ->set('school_name', 'Hillside Academy')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($hash, $child->student->fresh()->password);
    }

    public function test_login_email_must_stay_unique(): void
    {
        $child = Child::factory()->withStudentAccount()->create();
        $taken = User::factory()->create();

        Livewire::actingAs($child->parent)
            ->test('pages::parent.children.edit', ['child' => $child])
            ->set('student_email', $taken->email)
            ->call('save')
            ->assertHasErrors(['student_email' => 'unique']);
    }

    public function test_moving_into_a_new_key_stage_cancels_old_courses_and_asks_for_new_ones(): void
    {
        $gcseCourse = Course::factory()->keyStage(KeyStage::Gcse)->create();
        $child = $this->enrolledStudent($gcseCourse);
        $ks3Course = Course::factory()->keyStage(KeyStage::Ks3)->create();
        $child->enrolments()->create(['course_id' => $ks3Course->id, 'status' => EnrolmentStatus::Active, 'enrolled_at' => now()]);
        $child->update(['year_group' => 9, 'key_stage' => KeyStage::Ks3]);

        Livewire::actingAs($child->parent)
            ->test('pages::parent.children.edit', ['child' => $child])
            ->set('year_group', 10)
            ->assertSee('Year 10 is GCSE')
            ->call('save')
            ->assertRedirect(route('parent.children.courses', $child));

        $this->assertSame(KeyStage::Gcse, $child->fresh()->key_stage);
        $this->assertSame(EnrolmentStatus::Cancelled, $child->enrolments()->where('course_id', $ks3Course->id)->sole()->status);
        $this->assertSame(EnrolmentStatus::Active, $child->enrolments()->where('course_id', $gcseCourse->id)->sole()->status);
    }

    public function test_parents_cannot_edit_other_families_children(): void
    {
        $child = Child::factory()->create();

        $this->actingAs(User::factory()->parent()->create())
            ->get(route('parent.children.edit', $child))
            ->assertForbidden();
    }

    public function test_dashboard_links_to_the_edit_page(): void
    {
        $child = Child::factory()->create();

        $this->actingAs($child->parent)
            ->get(route('parent.dashboard'))
            ->assertSee(route('parent.children.edit', $child));
    }
}
