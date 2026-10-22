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
     * Seed a realistic KS3 and GCSE catalogue: course -> subjects -> topics,
     * each topic with materials and a short multiple-choice quiz.
     */
    public function run(): void
    {
        foreach ($this->catalogue() as $courseData) {
            $course = Course::firstOrCreate(
                ['title' => $courseData['title'], 'key_stage' => $courseData['key_stage']],
                [
                    'subject_area' => $courseData['subject_area'],
                    'summary' => $courseData['summary'],
                    'description' => $courseData['description'],
                    'price_per_hour' => $courseData['price_per_hour'],
                    'price_monthly' => $courseData['price_per_hour'] * 4,
                    'hours_per_month' => 4,
                    'is_featured' => $courseData['is_featured'],
                    'is_published' => true,
                ],
            );

            if ($course->subjects()->exists()) {
                continue;
            }

            foreach ($courseData['subjects'] as $subjectPosition => [$subjectName, $topics]) {
                $subject = $course->subjects()->create([
                    'name' => $subjectName,
                    'position' => $subjectPosition,
                ]);

                foreach ($topics as $topicPosition => $topicTitle) {
                    $topic = $subject->topics()->create([
                        'title' => $topicTitle,
                        'description' => "Core ideas, worked examples and practice for {$topicTitle}.",
                        'position' => $topicPosition,
                    ]);

                    $this->seedTopicContent($topic);
                }
            }
        }
    }

    private function seedTopicContent(Topic $topic): void
    {
        $topic->materials()->createMany([
            [
                'title' => "{$topic->title}: preparation guide",
                'description' => 'Read before the live lesson.',
                'type' => MaterialType::Link,
                'external_url' => 'https://www.bbc.co.uk/bitesize',
                'is_preparatory' => true,
                'position' => 0,
            ],
            [
                'title' => "{$topic->title}: walkthrough video",
                'type' => MaterialType::Video,
                'external_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'position' => 1,
            ],
        ]);

        Quiz::factory()
            ->withQuestions(5)
            ->for($topic)
            ->create([
                'title' => "{$topic->title} check-up",
                'description' => "Five quick questions on {$topic->title}.",
            ]);
    }

    /**
     * @return array<int, array{title: string, key_stage: KeyStage, subject_area: string, summary: string, description: string, price_per_hour: int, is_featured: bool, subjects: array<int, array{0: string, 1: array<int, string>}>}>
     */
    private function catalogue(): array
    {
        return [
            [
                'title' => 'Mathematics',
                'key_stage' => KeyStage::Ks3,
                'subject_area' => 'Mathematics',
                'summary' => 'Build fluency and confidence across number, algebra and geometry for Years 7 to 9.',
                'description' => 'Our KS3 Mathematics course follows the National Curriculum, combining weekly live lessons with targeted quizzes so gaps are spotted early and closed quickly.',
                'price_per_hour' => 2500,
                'is_featured' => true,
                'subjects' => [
                    ['Number', ['Fractions, decimals and percentages', 'Negative numbers', 'Powers and roots']],
                    ['Algebra', ['Expressions and substitution', 'Solving linear equations', 'Sequences']],
                    ['Geometry', ['Angles and polygons', 'Area and perimeter']],
                ],
            ],
            [
                'title' => 'English',
                'key_stage' => KeyStage::Ks3,
                'subject_area' => 'English',
                'summary' => 'Sharpen reading, analysis and creative writing skills ready for GCSE.',
                'description' => 'KS3 English develops close reading, persuasive and creative writing, and spelling, punctuation and grammar through engaging texts.',
                'price_per_hour' => 2500,
                'is_featured' => true,
                'subjects' => [
                    ['Reading', ['Inference and deduction', 'Language analysis']],
                    ['Writing', ['Descriptive writing', 'Persuasive writing', 'Punctuation for effect']],
                ],
            ],
            [
                'title' => 'Science',
                'key_stage' => KeyStage::Ks3,
                'subject_area' => 'Science',
                'summary' => 'Explore biology, chemistry and physics with clear explanations and practicals.',
                'description' => 'KS3 Science lays the foundations for combined or triple science at GCSE, with a focus on working scientifically.',
                'price_per_hour' => 2500,
                'is_featured' => false,
                'subjects' => [
                    ['Biology', ['Cells and organisation', 'Ecosystems']],
                    ['Chemistry', ['Particles and states of matter', 'Acids and alkalis']],
                    ['Physics', ['Forces and motion', 'Energy']],
                ],
            ],
            [
                'title' => 'Mathematics',
                'key_stage' => KeyStage::Gcse,
                'subject_area' => 'Mathematics',
                'summary' => 'Foundation and Higher tier preparation aligned to AQA, Edexcel and OCR.',
                'description' => 'GCSE Mathematics covers every topic on the specification with exam technique, past-paper practice and regular progress checks.',
                'price_per_hour' => 3000,
                'is_featured' => true,
                'subjects' => [
                    ['Algebra', ['Quadratic equations', 'Simultaneous equations', 'Graphs of functions']],
                    ['Geometry and measures', ['Pythagoras and trigonometry', 'Circle theorems']],
                    ['Probability and statistics', ['Probability trees', 'Averages and spread']],
                ],
            ],
            [
                'title' => 'English Language',
                'key_stage' => KeyStage::Gcse,
                'subject_area' => 'English',
                'summary' => 'Master Paper 1 and Paper 2 with structured reading and writing practice.',
                'description' => 'GCSE English Language focuses on the assessment objectives examiners reward, with model answers and timed practice.',
                'price_per_hour' => 3000,
                'is_featured' => true,
                'subjects' => [
                    ['Paper 1: Explorations in creative reading and writing', ['Language and structure questions', 'Narrative writing']],
                    ['Paper 2: Writers\' viewpoints and perspectives', ['Comparing sources', 'Writing to argue']],
                ],
            ],
            [
                'title' => 'Combined Science',
                'key_stage' => KeyStage::Gcse,
                'subject_area' => 'Science',
                'summary' => 'Trilogy-style coverage of biology, chemistry and physics with required practicals.',
                'description' => 'GCSE Combined Science prepares students for all six papers, with emphasis on required practicals and six-mark questions.',
                'price_per_hour' => 3000,
                'is_featured' => true,
                'subjects' => [
                    ['Biology', ['Cell biology', 'Infection and response']],
                    ['Chemistry', ['Atomic structure', 'Chemical changes']],
                    ['Physics', ['Electricity', 'Waves']],
                ],
            ],
        ];
    }
}
