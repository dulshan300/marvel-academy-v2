<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class WebsiteContentSeeder extends Seeder
{
    /**
     * Seed testimonials and FAQs shown on the public website, based on the
     * content of the existing Marvel Academy site.
     */
    public function run(): void
    {
        $testimonials = [
            ['Sarah P.', 'Parent', "Marvel Academy has been a game-changer for my child. The curriculum is engaging, the teachers are knowledgeable, and the learning environment is nurturing. My child's creativity and critical thinking skills have soared since joining the academy. I highly recommend Marvel Academy to all parents.", 5],
            ['Noah', 'Year 11 student', "I can't believe how much I've grown academically since joining Marvel Academy. The teachers challenge and motivate me to reach my full potential. I feel confident and prepared for any academic challenge that comes my way!", 5],
            ['Sam', 'Year 8 student', "The classes are interactive, and I've learned so much about teamwork, leadership and problem-solving. It's been an incredible journey.", 5],
            ['Lily', 'Year 7 student', "I never thought learning could be this exciting until I joined Marvel Academy. The teachers are amazing, and I've made so many friends.", 5],
        ];

        foreach ($testimonials as $position => [$name, $role, $content, $rating]) {
            Testimonial::firstOrCreate(
                ['author_name' => $name, 'author_role' => $role],
                ['content' => $content, 'rating' => $rating, 'is_verified' => false, 'is_published' => true, 'position' => $position],
            );
        }

        $faqs = [
            ['Getting started', 'Which year groups and subjects do you teach?', 'We teach Maths, Biology, Chemistry and Physics for Key Stage 3 (Years 7, 8 and 9) and GCSE (Years 10 and 11).'],
            ['Getting started', 'How much does it cost?', 'Prices are per child, per month: £19.99 for one subject, £31.99 for two, £47.99 for three and £63.99 for all four. Each subject includes 5 hours of live teaching a month.'],
            ['Getting started', 'Is there a free trial?', 'Yes. Every plan starts with a 7-day free trial, and you can cancel before it ends without being charged.'],
            ['Lessons', 'When are the live classes?', 'Each subject is taught twice a month in 2.5-hour live sessions on alternate Saturdays. The exact timings depend on year group and subject and are shown in your calendar.'],
            ['Lessons', 'How does the teaching work?', 'We cover one module of the syllabus each month. Before each lesson we send a question pack of past exam questions. In class, the tutor works through every question to show how to score maximum marks, then teaches the full theory.'],
            ['Lessons', 'How long does the programme run?', 'Our 10-month programme runs from September to June, finishing the syllabus before the exams. From July to August we run look-ahead classes so students get a head start on the next year.'],
            ['Lessons', 'Will I be reminded before a session?', 'Yes. Parents receive an email seven days before each live session with the preparation materials, and another two days before with joining instructions and the session link.'],
            ['Billing', 'Can I change or cancel my plan?', 'You can change or cancel your plan from your parent dashboard at any time. Changes take effect from your next billing date.'],
            ['Our tutors', 'Who are the tutors?', 'Our classes are led by a team of more than 40 engineers and scientists who graduated from leading universities, selected through a rigorous recruitment process.'],
        ];

        foreach ($faqs as $position => [$category, $question, $answer]) {
            Faq::updateOrCreate(
                ['question' => $question],
                ['category' => $category, 'answer' => $answer, 'is_published' => true, 'position' => $position],
            );
        }
    }
}
