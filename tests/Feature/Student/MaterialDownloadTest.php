<?php

namespace Tests\Feature\Student;

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
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class MaterialDownloadTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
    }

    private function pdfMaterial(Course $course): Material
    {
        $material = Material::factory()
            ->for(Topic::factory()->for(Subject::factory()->for($course)))
            ->create(['type' => MaterialType::Pdf, 'external_url' => null]);

        $material->addMedia(UploadedFile::fake()->create('study-plan.pdf', 50, 'application/pdf'))->toMediaCollection('file');

        return $material;
    }

    public function test_enrolled_student_is_sent_to_a_signed_file_url(): void
    {
        $course = Course::factory()->create();
        $material = $this->pdfMaterial($course);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->student)
            ->get(route('materials.download', $material))
            ->assertRedirectContains('study-plan.pdf');
    }

    public function test_parent_of_an_enrolled_child_can_download(): void
    {
        $course = Course::factory()->create();
        $material = $this->pdfMaterial($course);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->parent)
            ->get(route('materials.download', $material))
            ->assertRedirectContains('study-plan.pdf');
    }

    public function test_course_teacher_can_download(): void
    {
        $course = Course::factory()->create();
        $material = $this->pdfMaterial($course);
        $teacher = TeacherProfile::factory()->create()->user;
        $course->teachers()->attach($teacher);

        $this->actingAs($teacher)
            ->get(route('materials.download', $material))
            ->assertRedirectContains('study-plan.pdf');
    }

    public function test_student_whose_plan_lapsed_cannot_download(): void
    {
        $course = Course::factory()->create();
        $material = $this->pdfMaterial($course);
        $child = $this->enrolledStudent($course, subscribed: false);

        $this->actingAs($child->student)
            ->get(route('materials.download', $material))
            ->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $material = $this->pdfMaterial(Course::factory()->create());

        $this->get(route('materials.download', $material))->assertRedirect(route('login'));
    }

    public function test_pdf_type_rejects_other_file_formats(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $topic = Topic::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test('pages::admin.topics.edit', ['topic' => $topic])
            ->set('materialTitle', 'Notes')
            ->set('materialType', MaterialType::Pdf->value)
            ->set('materialFile', UploadedFile::fake()->create('notes.docx', 20, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'))
            ->call('addMaterial')
            ->assertHasErrors('materialFile');
    }

    public function test_presentation_type_accepts_pptx(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $topic = Topic::factory()->create();

        Livewire::actingAs(User::factory()->admin()->create())
            ->test('pages::admin.topics.edit', ['topic' => $topic])
            ->set('materialTitle', 'Lesson slides')
            ->set('materialType', MaterialType::Presentation->value)
            ->set('materialFile', UploadedFile::fake()->create('slides.pptx', 300, 'application/vnd.openxmlformats-officedocument.presentationml.presentation'))
            ->call('addMaterial')
            ->assertHasNoErrors();

        $this->assertSame(MaterialType::Presentation, $topic->materials()->sole()->type);
    }
}
