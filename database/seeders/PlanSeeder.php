<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Seed the subscription tiers. Stripe price IDs are filled in by
     * `php artisan billing:sync-plans` once Stripe keys are configured.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'One subject with a weekly live lesson.',
                'price_monthly' => 9900,
                'subjects_included' => 1,
                'hours_per_month' => 4,
                'trial_days' => 7,
                'features' => ['1 subject', '4 live hours a month', 'Topic quizzes with instant scores', 'Lesson recordings', 'Monthly parent progress email'],
                'is_featured' => false,
                'position' => 1,
            ],
            [
                'name' => 'Achiever',
                'slug' => 'achiever',
                'description' => 'Two subjects, the most popular choice for GCSE years.',
                'price_monthly' => 17900,
                'subjects_included' => 2,
                'hours_per_month' => 8,
                'trial_days' => 7,
                'features' => ['2 subjects', '8 live hours a month', 'Topic quizzes with instant scores', 'Lesson recordings & downloads', 'Weekly parent progress email'],
                'is_featured' => true,
                'position' => 2,
            ],
            [
                'name' => 'Scholar',
                'slug' => 'scholar',
                'description' => 'Three subjects for full KS3 or GCSE coverage.',
                'price_monthly' => 24900,
                'subjects_included' => 3,
                'hours_per_month' => 12,
                'trial_days' => 7,
                'features' => ['3 subjects', '12 live hours a month', 'Topic quizzes with instant scores', 'Lesson recordings & downloads', 'Weekly parent progress email', 'Priority tutor support'],
                'is_featured' => false,
                'position' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
