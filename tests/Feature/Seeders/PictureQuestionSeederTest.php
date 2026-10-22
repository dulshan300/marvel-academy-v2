<?php

namespace Tests\Feature\Seeders;

use App\Enums\KeyStage;
use App\Enums\QuestionType;
use App\Models\Course;
use App\Models\Question;
use App\Models\Quiz;
use App\Models\Subject;
use App\Models\Topic;
use Database\Seeders\PictureQuestionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PictureQuestionSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_adds_one_picture_question_with_a_png_graph_and_a_single_correct_answer(): void
    {
        Storage::fake('s3');
        $quiz = $this->starterQuiz(KeyStage::Gcse, 'Mathematics');

        $this->seed(PictureQuestionSeeder::class);
        $this->seed(PictureQuestionSeeder::class);

        $question = Question::where('type', QuestionType::Picture)->with('options')->sole();
        $this->assertTrue($question->quiz->is($quiz));
        $this->assertSame('What is the equation of the line?', $question->prompt);
        $this->assertSame('y = 2x + 1', $question->options->where('is_correct', true)->sole()->text);
        $this->assertCount(4, $question->options);

        $media = $question->getFirstMedia('image');
        $this->assertSame('image/png', $media->mime_type);
        $this->assertStringStartsWith("\x89PNG", Storage::disk('s3')->get($media->getPathRelativeToRoot()));
    }

    public function test_courses_without_a_chart_question_are_left_alone(): void
    {
        Storage::fake('s3');
        $this->starterQuiz(KeyStage::Ks3, 'Chemistry');

        $this->seed(PictureQuestionSeeder::class);

        $this->assertSame(0, Question::where('type', QuestionType::Picture)->count());
    }

    private function starterQuiz(KeyStage $keyStage, string $subjectArea): Quiz
    {
        $course = Course::factory()->keyStage($keyStage)->create(['subject_area' => $subjectArea]);

        return Quiz::factory()->withQuestions(2)->for(Topic::factory()->for(Subject::factory()->for($course)))->create();
    }
}
