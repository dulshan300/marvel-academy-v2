<?php

namespace Tests\Feature\Parent;

use App\Enums\KeyStage;
use App\Enums\Role;
use App\Models\Child;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AddChildTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_parent_can_add_a_child_with_a_student_login(): void
    {
        $parent = User::factory()->parent()->create();

        Livewire::actingAs($parent)
            ->test('pages::parent.children.create')
            ->set('first_name', 'Aarav')
            ->set('last_name', 'Sharma')
            ->set('year_group', 10)
            ->set('student_email', 'Aarav@Example.co.uk')
            ->set('student_password', 'student123')
            ->call('save')
            ->assertHasNoErrors();

        $child = Child::sole();
        $student = User::where('email', 'aarav@example.co.uk')->sole();

        $this->assertTrue($child->parent->is($parent));
        $this->assertTrue($child->student->is($student));
        $this->assertSame(KeyStage::Gcse, $child->key_stage);
        $this->assertSame(Role::Student, $student->primaryRole());
    }

    public function test_student_login_email_must_be_unique(): void
    {
        $parent = User::factory()->parent()->create();
        User::factory()->create(['email' => 'taken@example.co.uk']);

        Livewire::actingAs($parent)
            ->test('pages::parent.children.create')
            ->set('first_name', 'Aarav')
            ->set('last_name', 'Sharma')
            ->set('year_group', 8)
            ->set('student_email', 'taken@example.co.uk')
            ->set('student_password', 'student123')
            ->call('save')
            ->assertHasErrors(['student_email' => 'unique']);

        $this->assertDatabaseCount('children', 0);
    }

    public function test_year_group_outside_ks3_and_gcse_is_rejected(): void
    {
        Livewire::actingAs(User::factory()->parent()->create())
            ->test('pages::parent.children.create')
            ->set('year_group', 12)
            ->call('save')
            ->assertHasErrors(['year_group' => 'between']);
    }

    public function test_non_parents_cannot_open_the_page(): void
    {
        $this->actingAs(User::factory()->student()->create())
            ->get(route('parent.children.create'))
            ->assertForbidden();
    }

    /**
     * @return array<string, array{int, KeyStage}>
     */
    public static function yearGroups(): array
    {
        return [
            'year 6' => [6, KeyStage::Ks2],
            'year 7' => [7, KeyStage::Ks3],
            'year 9' => [9, KeyStage::Ks3],
            'year 10' => [10, KeyStage::Gcse],
            'year 11' => [11, KeyStage::Gcse],
            'year 12' => [12, KeyStage::ALevel],
        ];
    }

    #[DataProvider('yearGroups')]
    public function test_key_stage_is_derived_from_year_group(int $yearGroup, KeyStage $expected): void
    {
        $this->assertSame($expected, Child::keyStageForYear($yearGroup));
    }
}
