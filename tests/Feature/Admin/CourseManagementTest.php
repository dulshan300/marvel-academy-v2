<?php

namespace Tests\Feature\Admin;

use App\Enums\KeyStage;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\Material;
use App\Models\Subject;
use App\Models\TeacherProfile;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CourseManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_creates_a_course_with_prices_in_pence_and_tutors(): void
    {
        $teacher = TeacherProfile::factory()->create()->user;

        Livewire::actingAs($this->admin)
            ->test('pages::admin.courses.edit')
            ->set('title', 'Physics')
            ->set('key_stage', KeyStage::Gcse->value)
            ->set('subject_area', 'Physics')
            ->set('summary', 'Live GCSE Physics.')
            ->set('price_per_hour', '4.00')
            ->set('price_monthly', '19.99')
            ->set('hours_per_month', 5)
            ->set('teacherIds', [(string) $teacher->id])
            ->call('save')
            ->assertHasNoErrors();

        $course = Course::sole();
        $this->assertSame('gcse-physics', $course->slug);
        $this->assertSame(400, $course->price_per_hour);
        $this->assertSame(1999, $course->price_monthly);
        $this->assertTrue($course->teachers->first()->is($teacher));
    }

    public function test_non_teachers_cannot_be_assigned_as_tutors(): void
    {
        $parent = User::factory()->parent()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.courses.edit')
            ->set('title', 'Physics')
            ->set('subject_area', 'Physics')
            ->set('summary', 'Summary')
            ->set('teacherIds', [$parent->id])
            ->call('save')
            ->assertHasErrors('teacherIds.0');
    }

    public function test_cover_image_is_stored_with_media_library(): void
    {
        Storage::fake('public');
        $course = Course::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.courses.edit', ['course' => $course])
            ->set('cover', UploadedFile::fake()->image('cover.jpg', 1200, 800))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotNull($course->fresh()->getFirstMedia('cover'));
    }

    public function test_structure_editor_adds_reorders_and_deletes_subjects_and_topics(): void
    {
        $course = Course::factory()->create();

        $component = Livewire::actingAs($this->admin)
            ->test('pages::admin.courses.edit', ['course' => $course])
            ->set('newSubjectName', 'Year 10')->call('addSubject')
            ->set('newSubjectName', 'Year 11')->call('addSubject');

        [$year10, $year11] = $course->subjects()->get();

        $component->call('moveSubject', $year11->id, -1);
        $this->assertSame(['Year 11', 'Year 10'], $course->subjects()->pluck('name')->all());

        $component->set("newTopicTitles.{$year10->id}", 'Cell Biology')->call('addTopic', $year10->id);
        $topic = $year10->topics()->sole();
        $this->assertSame('Cell Biology', $topic->title);

        $component->call('deleteTopic', $topic->id);
        $this->assertModelMissing($topic);

        $component->call('deleteSubject', $year11->id);
        $this->assertModelMissing($year11);
    }

    public function test_structure_editor_ignores_subjects_from_other_courses(): void
    {
        $course = Course::factory()->create();
        $foreignSubject = Subject::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.courses.edit', ['course' => $course])
            ->call('deleteSubject', $foreignSubject->id)
            ->assertNotFound();

        $this->assertModelExists($foreignSubject);
    }

    public function test_uploaded_material_is_stored_on_the_private_disk(): void
    {
        Storage::fake('s3');
        $topic = Topic::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.topics.edit', ['topic' => $topic])
            ->set('materialTitle', 'Question pack')
            ->set('materialType', MaterialType::Document->value)
            ->set('materialFile', UploadedFile::fake()->create('Pack 1.pdf', 200, 'application/pdf'))
            ->set('materialIsPreparatory', true)
            ->call('addMaterial')
            ->assertHasNoErrors();

        $material = Material::sole();
        $media = $material->getFirstMedia('file');

        $this->assertTrue($material->is_preparatory);
        $this->assertSame('s3', $media->disk);
        $this->assertSame('pack-1.pdf', $media->file_name);
        Storage::disk('s3')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_link_material_requires_a_url(): void
    {
        $topic = Topic::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.topics.edit', ['topic' => $topic])
            ->set('materialTitle', 'Bitesize')
            ->set('materialType', MaterialType::Link->value)
            ->call('addMaterial')
            ->assertHasErrors(['materialUrl' => 'required']);
    }

    public function test_creating_a_quiz_redirects_to_the_quiz_editor(): void
    {
        $topic = Topic::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.topics.edit', ['topic' => $topic])
            ->set('quizTitle', 'Cells check-up')
            ->call('createQuiz')
            ->assertRedirect(route('admin.quizzes.edit', $topic->quizzes()->sole()));

        $this->assertFalse($topic->quizzes()->sole()->is_published);
    }
}
