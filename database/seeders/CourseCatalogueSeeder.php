<?php

namespace Database\Seeders;

use App\Enums\KeyStage;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Topic;
use Illuminate\Database\Seeder;

class CourseCatalogueSeeder extends Seeder
{
    /**
     * Months in which each syllabus module is taught, in order.
     *
     * @var array<int, string>
     */
    private const MONTHS = ['September', 'October', 'November', 'December', 'January', 'February', 'March', 'April'];

    /**
     * Seed the Marvel Academy catalogue: one course per subject and key stage,
     * a subject per year group, and a topic per monthly module.
     */
    public function run(): void
    {
        $syllabus = require database_path('seeders/data/syllabus.php');
        $starterQuizzes = require database_path('seeders/data/starter-quizzes.php');

        foreach ($syllabus as $stageValue => $subjectAreas) {
            $keyStage = KeyStage::from($stageValue);

            foreach ($subjectAreas as $subjectArea => $yearGroups) {
                $course = Course::firstOrCreate(
                    ['title' => $subjectArea, 'key_stage' => $keyStage],
                    [
                        'subject_area' => $subjectArea,
                        'summary' => $this->summary($keyStage, $subjectArea),
                        'description' => $this->description($keyStage, $subjectArea),
                        'price_per_hour' => 400,
                        'price_monthly' => 1999,
                        'hours_per_month' => 5,
                        'is_featured' => true,
                        'is_published' => true,
                    ],
                );

                if ($course->subjects()->exists()) {
                    continue;
                }

                $firstTopic = null;

                foreach (array_keys($yearGroups) as $subjectPosition => $yearGroup) {
                    $subject = $course->subjects()->create([
                        'name' => "Year {$yearGroup}",
                        'description' => "{$subjectArea} modules for Year {$yearGroup}, taught September to April with revision from May.",
                        'position' => $subjectPosition,
                    ]);

                    foreach ($yearGroups[$yearGroup] as $topicPosition => $moduleTitle) {
                        $topic = $subject->topics()->create([
                            'title' => $moduleTitle,
                            'description' => self::MONTHS[$topicPosition].' module: past-paper questions, worked solutions and a full theory lesson.',
                            'position' => $topicPosition,
                        ]);

                        $this->seedMaterials($topic, $keyStage, $subjectArea);
                        $firstTopic ??= $topic;
                    }
                }

                if ($firstTopic !== null && isset($starterQuizzes["{$stageValue}.{$subjectArea}"])) {
                    $this->seedQuiz($firstTopic, $starterQuizzes["{$stageValue}.{$subjectArea}"]);
                }
            }
        }
    }

    private function seedMaterials(Topic $topic, KeyStage $keyStage, string $subjectArea): void
    {
        $topic->materials()->create([
            'title' => "{$topic->title}: revision reading",
            'description' => "Background reading on {$topic->title} for {$keyStage->label()} {$subjectArea}. The tutor's past-paper question pack is uploaded by staff before each session.",
            'type' => MaterialType::Link,
            'external_url' => 'https://www.bbc.co.uk/bitesize',
            'is_preparatory' => true,
            'position' => 0,
        ]);
    }

    /**
     * @param  array<int, array{0: string, 1: array<int, string>, 2: string}>  $questions
     */
    private function seedQuiz(Topic $topic, array $questions): void
    {
        $quiz = Quiz::create([
            'topic_id' => $topic->id,
            'title' => "{$topic->title} check-up",
            'description' => 'Five quick questions to check your understanding of this module.',
            'pass_mark' => 60,
            'is_published' => true,
        ]);

        foreach ($questions as $questionPosition => [$prompt, $options, $explanation]) {
            $question = $quiz->questions()->create([
                'prompt' => $prompt,
                'explanation' => $explanation,
                'marks' => 1,
                'position' => $questionPosition,
            ]);

            $correctAnswer = $options[0];

            foreach (collect($options)->shuffle()->values() as $optionPosition => $text) {
                $question->options()->create([
                    'text' => $text,
                    'is_correct' => $text === $correctAnswer,
                    'position' => $optionPosition,
                ]);
            }
        }
    }

    private function summary(KeyStage $keyStage, string $subjectArea): string
    {
        return "Live {$keyStage->label()} {$subjectArea} for {$keyStage->yearRange()}: 5 hours of live teaching a month, past-paper practice and topic quizzes.";
    }

    private function description(KeyStage $keyStage, string $subjectArea): string
    {
        return "Our {$keyStage->label()} {$subjectArea} course follows the UK curriculum, covering one module of the syllabus each month from September to April, with revision and look-ahead classes from May to August.\n\n"
            ."Each module is taught live twice a month in 2.5-hour sessions on alternate Saturdays. Before every lesson students receive a question pack of past exam questions so they can gauge their understanding. In class, our engineers and scientists work through every question, showing how to score maximum marks, then reinforce the topic with a full theory lesson.\n\n"
            .'Sessions combine past-paper questions, theory, whiteboard demonstrations, interactive quizzes and dedicated question-and-answer time.';
    }
}
