<?php

namespace Tests\Feature;

use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Concerns\CreatesStudents;
use Tests\TestCase;

class PictureQuestionTest extends TestCase
{
    use CreatesStudents, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('s3');
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_adds_a_picture_question_with_an_image_and_description(): void
    {
        $quiz = Quiz::factory()->create();

        $this->fillQuestion(Livewire::actingAs($this->admin)->test('pages::admin.quizzes.edit', ['quiz' => $quiz]))
            ->set('questionType', QuestionType::Picture->value)
            ->set('image', UploadedFile::fake()->image('graph.png', 800, 600))
            ->set('questionDescription', 'The graph shows a cyclist travelling 30 km in 2 hours.')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $question = $quiz->questions()->sole();
        $this->assertSame(QuestionType::Picture, $question->type);
        $this->assertSame('The graph shows a cyclist travelling 30 km in 2 hours.', $question->description);
        $media = $question->getFirstMedia('image');
        $this->assertSame('s3', $media->disk);
        $this->assertNotSame('graph.png', $media->file_name, 'Uploaded file names are replaced.');
    }

    public function test_picture_question_needs_an_image_of_an_allowed_type(): void
    {
        $quiz = Quiz::factory()->create();
        $component = $this->fillQuestion(Livewire::actingAs($this->admin)->test('pages::admin.quizzes.edit', ['quiz' => $quiz]))
            ->set('questionType', QuestionType::Picture->value);

        $component->call('saveQuestion')->assertHasErrors(['image' => 'required']);
        $component->set('image', UploadedFile::fake()->create('diagram.svg', 10, 'image/svg+xml'))->call('saveQuestion')->assertHasErrors('image');

        $this->assertSame(0, $quiz->questions()->count());
    }

    public function test_editing_keeps_the_image_and_switching_to_text_removes_it(): void
    {
        $question = $this->pictureQuestion(Quiz::factory()->create());
        $quiz = $question->quiz;

        Livewire::actingAs($this->admin)->test('pages::admin.quizzes.edit', ['quiz' => $quiz])
            ->call('editQuestion', $question->id)
            ->assertSet('hasStoredImage', true)
            ->set('prompt', 'Updated question?')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $this->assertNotNull($question->fresh()->getFirstMedia('image'));

        Livewire::actingAs($this->admin)->test('pages::admin.quizzes.edit', ['quiz' => $quiz])
            ->call('editQuestion', $question->id)
            ->set('questionType', QuestionType::Text->value)
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $question->refresh();
        $this->assertSame(QuestionType::Text, $question->type);
        $this->assertNull($question->description);
        $this->assertNull($question->getFirstMedia('image'));
    }

    public function test_student_sees_the_picture_and_description_when_taking_the_quiz(): void
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->for(Topic::factory()->for(Subject::factory()->for($course)))->create();
        $question = $this->pictureQuestion($quiz);
        $child = $this->enrolledStudent($course);

        $this->actingAs($child->student)
            ->get(route('student.quizzes.take', $quiz))
            ->assertOk()
            ->assertSee(route('questions.image', $question))
            ->assertSee($question->description);
    }

    public function test_question_images_are_only_served_to_people_who_may_see_the_quiz(): void
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->for(Topic::factory()->for(Subject::factory()->for($course)))->create();
        $question = $this->pictureQuestion($quiz);
        $child = $this->enrolledStudent($course);
        $outsider = $this->enrolledStudent(Course::factory()->create());

        $this->get(route('questions.image', $question))->assertRedirect(route('login'));
        $this->actingAs($child->student)->get(route('questions.image', $question))->assertRedirect();
        $this->actingAs($this->admin)->get(route('questions.image', $question))->assertRedirect();
        $this->actingAs($outsider->student)->get(route('questions.image', $question))->assertForbidden();
        $this->actingAs($outsider->parent)->get(route('questions.image', $question))->assertForbidden();
    }

    public function test_parent_can_see_the_image_on_their_childs_result(): void
    {
        $course = Course::factory()->create();
        $quiz = Quiz::factory()->for(Topic::factory()->for(Subject::factory()->for($course)))->create();
        $question = $this->pictureQuestion($quiz);
        $child = $this->enrolledStudent($course);
        $quiz->attempts()->create(['child_id' => $child->id, 'started_at' => now(), 'completed_at' => now()]);

        $this->actingAs($child->parent)->get(route('questions.image', $question))->assertRedirect();
    }

    private function fillQuestion($component)
    {
        return $component->call('newQuestion')
            ->set('prompt', 'How fast is the cyclist going?')
            ->set('options.0.text', '15 km/h')
            ->set('options.1.text', '30 km/h')
            ->set('options.2.text', '60 km/h')
            ->set('options.3.text', '2 km/h')
            ->set('correctIndex', 0);
    }

    private function pictureQuestion(Quiz $quiz): Question
    {
        $question = Question::factory()->picture()->for($quiz)->create();
        QuestionOption::factory()->correct()->for($question)->create(['position' => 0]);
        QuestionOption::factory()->for($question)->create(['position' => 1]);
        $question->addMedia(UploadedFile::fake()->image('shape.png'))->toMediaCollection('image');

        return $question;
    }
}
