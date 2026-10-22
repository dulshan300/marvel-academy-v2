<?php

namespace Database\Seeders;

use App\Enums\KeyStage;
use App\Enums\QuestionType;
use App\Models\Quiz;
use Database\Seeders\Support\DemoChart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Adds a picture question with a generated graph to the starter quiz of the
 * courses where a chart makes sense. Safe to re-run: quizzes that already
 * have a picture question are skipped.
 */
class PictureQuestionSeeder extends Seeder
{
    public function run(): void
    {
        if (! config('marvel.seed_demo_material_files')) {
            return;
        }

        foreach ($this->questions() as $key => $question) {
            [$keyStage, $subjectArea] = explode('.', $key);

            $quiz = Quiz::query()
                ->whereHas('topic.subject.course', fn ($query) => $query
                    ->where('key_stage', KeyStage::from($keyStage))
                    ->where('subject_area', $subjectArea))
                ->whereDoesntHave('questions', fn ($query) => $query->where('type', QuestionType::Picture))
                ->orderBy('id')
                ->first();

            if ($quiz === null) {
                continue;
            }

            $model = $quiz->questions()->create([
                'type' => QuestionType::Picture,
                'description' => $question['description'],
                'prompt' => $question['prompt'],
                'explanation' => $question['explanation'],
                'marks' => 1,
                'position' => (int) $quiz->questions()->max('position') + 1,
            ]);

            $correctAnswer = $question['options'][0];

            foreach (collect($question['options'])->shuffle()->values() as $position => $text) {
                $model->options()->create(['text' => $text, 'is_correct' => $text === $correctAnswer, 'position' => $position]);
            }

            $model->addMediaFromString($question['chart']())
                ->usingFileName(Str::slug($question['prompt']).'.png')
                ->toMediaCollection('image');
        }
    }

    /**
     * Picture questions keyed by "{key stage}.{subject area}", correct option first.
     *
     * @return array<string, array{chart: callable(): string, description: string, prompt: string, options: array<int, string>, explanation: string}>
     */
    private function questions(): array
    {
        return [
            'ks3.Mathematics' => [
                'chart' => fn () => DemoChart::barChart('Favourite fruit of Class 7B', 'Fruit', 'Number of pupils', 14, 2, [
                    'Apples' => 12, 'Bananas' => 7, 'Oranges' => 9, 'Grapes' => 4,
                ]),
                'description' => 'The bar chart shows the favourite fruit of the pupils in Class 7B. Each bar shows how many pupils chose that fruit.',
                'prompt' => 'How many more pupils chose apples than bananas?',
                'options' => ['5', '19', '7', '12'],
                'explanation' => '12 pupils chose apples and 7 chose bananas, so 12 − 7 = 5 more chose apples.',
            ],
            'ks3.Physics' => [
                'chart' => fn () => DemoChart::lineGraph('A cyclist\'s journey', 'Time (hours)', 'Distance (km)', 4, 60, 1, 10, [
                    [0, 0], [2, 30], [3, 30], [4, 50],
                ]),
                'description' => 'The distance–time graph shows a cyclist\'s journey. The cyclist rides for 2 hours, stops for a rest, then rides again.',
                'prompt' => 'What was the cyclist\'s speed during the first 2 hours?',
                'options' => ['15 km/h', '30 km/h', '60 km/h', '2 km/h'],
                'explanation' => 'Speed = distance ÷ time. The cyclist travelled 30 km in 2 hours, so 30 ÷ 2 = 15 km/h.',
            ],
            'gcse.Mathematics' => [
                'chart' => fn () => DemoChart::lineGraph('A straight-line graph', 'x', 'y', 5, 12, 1, 2, [
                    [0, 1], [2, 5], [4, 9], [5, 11],
                ]),
                'description' => 'The graph shows a straight line. It crosses the y-axis at 1 and passes through the points (2, 5) and (4, 9).',
                'prompt' => 'What is the equation of the line?',
                'options' => ['y = 2x + 1', 'y = x + 2', 'y = 2x − 1', 'y = 4x + 1'],
                'explanation' => 'The y-intercept is 1. The gradient is the change in y ÷ change in x = (9 − 5) ÷ (4 − 2) = 2, so y = 2x + 1.',
            ],
            'gcse.Physics' => [
                'chart' => fn () => DemoChart::lineGraph('Current through a resistor', 'Potential difference (V)', 'Current (A)', 12, 4, 2, 1, [
                    [0, 0], [4, 1], [8, 2], [12, 3],
                ]),
                'description' => 'A student measured the current through a fixed resistor at different potential differences. The graph is a straight line through the origin, passing through (8 V, 2 A).',
                'prompt' => 'What is the resistance of the resistor?',
                'options' => ['4 Ω', '0.25 Ω', '16 Ω', '2 Ω'],
                'explanation' => 'Resistance = potential difference ÷ current. At 8 V the current is 2 A, so R = 8 ÷ 2 = 4 Ω.',
            ],
            'gcse.Biology' => [
                'chart' => fn () => DemoChart::barChart('Typical length of different cells', 'Type of cell', 'Length (micrometres, µm)', 70, 10, [
                    'Bacterium' => 2, 'Red blood cell' => 8, 'Cheek cell' => 40, 'Leaf cell' => 60,
                ]),
                'description' => 'The bar chart shows the typical length of four types of cell, measured in micrometres (µm). 1 µm is one thousandth of a millimetre.',
                'prompt' => 'How many times longer is the leaf cell than the red blood cell?',
                'options' => ['7.5 times', '52 times', '68 times', '0.13 times'],
                'explanation' => 'Divide the larger length by the smaller: 60 ÷ 8 = 7.5, so the leaf cell is 7.5 times longer.',
            ],
        ];
    }
}
