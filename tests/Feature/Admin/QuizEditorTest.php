<?php

namespace Tests\Feature\Admin;

use App\Models\Quiz;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class QuizEditorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->admin = User::factory()->admin()->create();
    }

    public function test_admin_adds_a_question_with_one_correct_option(): void
    {
        $quiz = Quiz::factory()->unpublished()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.quizzes.edit', ['quiz' => $quiz])
            ->call('newQuestion')
            ->set('prompt', 'What is 2 + 2?')
            ->set('options.0.text', '4')
            ->set('options.1.text', '3')
            ->set('options.2.text', '5')
            ->set('options.3.text', '22')
            ->set('correctIndex', 0)
            ->set('explanation', 'Two plus two is four.')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $question = $quiz->questions()->with('options')->sole();
        $this->assertSame('What is 2 + 2?', $question->prompt);
        $this->assertCount(4, $question->options);
        $this->assertSame('4', $question->options->where('is_correct', true)->sole()->text);
    }

    public function test_question_requires_a_correct_answer_and_distinct_options(): void
    {
        $quiz = Quiz::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.quizzes.edit', ['quiz' => $quiz])
            ->call('newQuestion')
            ->set('prompt', 'Pick one')
            ->set('options.0.text', 'Same')
            ->set('options.1.text', 'Same')
            ->set('options.2.text', 'Other')
            ->set('options.3.text', 'Another')
            ->call('saveQuestion')
            ->assertHasErrors(['correctIndex', 'options.1.text']);

        $this->assertSame(0, $quiz->questions()->count());
    }

    public function test_editing_keeps_existing_option_ids_for_past_attempts(): void
    {
        $quiz = Quiz::factory()->withQuestions(1)->create();
        $question = $quiz->questions()->with('options')->sole();
        $originalIds = $question->options->pluck('id')->all();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.quizzes.edit', ['quiz' => $quiz])
            ->call('editQuestion', $question->id)
            ->set('options.0.text', 'Reworded option')
            ->call('saveQuestion')
            ->assertHasNoErrors();

        $this->assertSame($originalIds, $question->options()->pluck('id')->all());
        $this->assertSame('Reworded option', $question->options()->first()->text);
    }

    public function test_quiz_without_questions_cannot_be_published(): void
    {
        $quiz = Quiz::factory()->unpublished()->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.quizzes.edit', ['quiz' => $quiz])
            ->set('is_published', true)
            ->call('saveQuiz')
            ->assertHasErrors('is_published');

        $this->assertFalse($quiz->fresh()->is_published);
    }

    public function test_quiz_with_valid_questions_can_be_published(): void
    {
        $quiz = Quiz::factory()->unpublished()->withQuestions(2)->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.quizzes.edit', ['quiz' => $quiz])
            ->set('is_published', true)
            ->set('pass_mark', 70)
            ->call('saveQuiz')
            ->assertHasNoErrors();

        $this->assertTrue($quiz->fresh()->is_published);
        $this->assertSame(70, $quiz->fresh()->pass_mark);
    }

    public function test_deleting_the_last_question_unpublishes_the_quiz(): void
    {
        $quiz = Quiz::factory()->withQuestions(1)->create();

        Livewire::actingAs($this->admin)
            ->test('pages::admin.quizzes.edit', ['quiz' => $quiz])
            ->call('deleteQuestion', $quiz->questions()->sole()->id);

        $this->assertFalse($quiz->fresh()->is_published);
    }
}
