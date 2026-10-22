<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed the subscription tiers, priced per child by number of subjects as on
     * the existing Marvel Academy site. Stripe price IDs are filled in by
     * `php artisan billing:sync-plans` once Stripe keys are configured.
     */
    public function run(): void
    {
        $tiers = [
            ['one-subject', '1 Subject', 1999, false, 'Focus on the subject that matters most right now.'],
            ['two-subjects', '2 Subjects', 3199, true, 'Our most popular choice, ideal for Maths plus a science.'],
            ['three-subjects', '3 Subjects', 4799, false, 'Broad support across Maths and the sciences.'],
            ['four-subjects', '4 Subjects', 6399, false, 'Complete coverage: Maths, Biology, Chemistry and Physics.'],
        ];

        foreach ($tiers as $position => [$slug, $name, $price, $isFeatured, $description]) {
            $subjects = $position + 1;
            $hours = $subjects * 5;

            Plan::updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'price_monthly' => $price,
                'subjects_included' => $subjects,
                'hours_per_month' => $hours,
                'trial_days' => 7,
                'features' => [
                    "{$hours} hours of live teaching a month",
                    'Each subject taught twice a month (2.5-hour sessions)',
                    'Past-paper question packs before every lesson',
                    'Topic quizzes with instant scores',
                    'Session recordings and downloadable notes',
                    'Parent progress emails',
                ],
                'is_featured' => $isFeatured,
                'is_active' => true,
                'position' => $position,
            ]);
        }
    }
}
