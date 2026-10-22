<?php

namespace Database\Seeders;

use App\Enums\KeyStage;
use App\Enums\MaterialType;
use App\Models\Course;
use App\Models\Quiz;
use App\Models\Topic;
use Database\Seeders\Support\DemoPdf;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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

                        $this->seedMaterials($topic, $keyStage, $subjectArea, $yearGroup, self::MONTHS[$topicPosition]);
                        $firstTopic ??= $topic;
                    }
                }

                if ($firstTopic !== null && isset($starterQuizzes["{$stageValue}.{$subjectArea}"])) {
                    $this->seedQuiz($firstTopic, $starterQuizzes["{$stageValue}.{$subjectArea}"]);
                }
            }
        }
    }

    /**
     * Each topic gets two PDFs (course content and a study plan) plus a
     * reading link. Set SEED_DEMO_MATERIAL_FILES=false to skip the uploads.
     */
    private function seedMaterials(Topic $topic, KeyStage $keyStage, string $subjectArea, int $yearGroup, string $month): void
    {
        $courseName = "{$keyStage->label()} {$subjectArea}";

        if (config('marvel.seed_demo_material_files')) {
            $this->attachPdf(
                $topic,
                "{$topic->title}: course content",
                "Lesson notes, key ideas and practice for {$topic->title}.",
                $this->courseContentPdf($topic->title, $courseName, $yearGroup, $month),
                isPreparatory: false,
                position: 0,
            );

            $this->attachPdf(
                $topic,
                "{$topic->title}: study plan",
                'What to do before, between and after this month\'s two live lessons.',
                $this->studyPlanPdf($topic->title, $courseName, $yearGroup, $month),
                isPreparatory: true,
                position: 1,
            );
        }

        $topic->materials()->create([
            'title' => "{$topic->title}: further reading",
            'description' => "Background reading on {$topic->title} for {$courseName}.",
            'type' => MaterialType::Link,
            'external_url' => 'https://www.bbc.co.uk/bitesize',
            'is_preparatory' => false,
            'position' => 2,
        ]);
    }

    private function attachPdf(Topic $topic, string $title, string $description, string $contents, bool $isPreparatory, int $position): void
    {
        $material = $topic->materials()->create([
            'title' => $title,
            'description' => $description,
            'type' => MaterialType::Pdf,
            'is_preparatory' => $isPreparatory,
            'position' => $position,
        ]);

        $material->addMediaFromString($contents)
            ->usingName($title)
            ->usingFileName(Str::slug($title).'.pdf')
            ->toMediaCollection('file');
    }

    private function courseContentPdf(string $topic, string $course, int $yearGroup, string $month): string
    {
        return (new DemoPdf("{$topic}: Course Content"))
            ->paragraph("{$course} - Year {$yearGroup} - {$month} module")
            ->heading('What this module covers')
            ->paragraph("This module introduces {$topic} and builds the understanding needed to answer exam-style questions with confidence. It is taught across two live 2.5-hour lessons this month.")
            ->heading('Learning objectives')
            ->bullets([
                "Explain the key terms and ideas in {$topic} using correct vocabulary.",
                'Apply the method to familiar and unfamiliar exam-style questions.',
                'Show full working or reasoning to secure method and accuracy marks.',
                'Recognise common mistakes and how examiners penalise them.',
                'Link this module to earlier topics and to next month\'s module.',
            ])
            ->heading('Lesson 1: past-paper questions first')
            ->paragraph('We begin with the question pack you received before the lesson. Your tutor works through every question live, explaining how each mark is earned and where students typically lose marks.')
            ->heading('Lesson 2: theory and exam technique')
            ->paragraph("Once the questions are covered, the tutor teaches the full theory behind {$topic}, with whiteboard demonstrations, worked examples and short interactive checks.")
            ->heading('Key vocabulary')
            ->paragraph('Write a one-line definition for each key term introduced in the lessons. Keep these definitions in your revision notebook and test yourself weekly.')
            ->heading('Check your understanding')
            ->bullets([
                'Complete the topic quiz in your student portal after the second lesson.',
                'Aim for at least 60%; review every explanation, then retake to improve.',
                'Bring any questions you could not answer to the next live session.',
            ])
            ->render();
    }

    private function studyPlanPdf(string $topic, string $course, int $yearGroup, string $month): string
    {
        return (new DemoPdf("{$topic}: Study Plan"))
            ->paragraph("{$course} - Year {$yearGroup} - {$month}")
            ->paragraph('Little and often works best. Plan 20-30 minutes of independent study on three days each week.')
            ->heading('Before lesson 1 (week 1)')
            ->bullets([
                'Read this study plan and the course content notes for the module.',
                'Attempt as many questions from the question pack as you can. Mark the ones you find difficult.',
                'Check your login and that you can open the lesson page in the student portal.',
            ])
            ->heading('After lesson 1 (week 2)')
            ->bullets([
                'Redo any question you got wrong, without looking at the worked solution.',
                'Write a summary of the method in your own words.',
                'Watch the recording of any part you want to revisit.',
            ])
            ->heading('Before lesson 2 (week 3)')
            ->bullets([
                'Learn the key vocabulary using flashcards or self-quizzing.',
                'Note down any questions to ask your tutor.',
            ])
            ->heading('After lesson 2 (week 4)')
            ->bullets([
                "Take the {$topic} quiz in the student portal and review every explanation.",
                'Retake the quiz a few days later to check the learning has stuck.',
                'Add the module to your revision timetable for the exam season.',
            ])
            ->heading('For parents')
            ->paragraph('You will receive reminder emails seven days and two days before each live lesson, and an email with your child\'s score whenever they complete a topic quiz.')
            ->render();
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
