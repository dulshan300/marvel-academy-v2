<?php

namespace Database\Seeders;

use App\Models\Faq;
use App\Models\Testimonial;
use Illuminate\Database\Seeder;

class WebsiteContentSeeder extends Seeder
{
    /**
     * Seed testimonials and FAQs shown on the public website.
     */
    public function run(): void
    {
        $testimonials = [
            ['Sarah K.', 'Parent of Year 10 student', 'My daughter went from a grade 4 to a grade 7 in Maths in two terms. The weekly progress emails meant I always knew exactly where she was.', 5],
            ['Daniel O.', 'Year 11 student', 'The live lessons are relaxed and you can ask anything. The quizzes after each topic showed me what to revise before my mocks.', 5],
            ['Priya S.', 'Parent of Year 8 student', 'Booking was simple and the tutors are brilliant. My son actually looks forward to his English sessions now.', 5],
            ['James T.', 'Parent of Year 9 student', 'Great value compared with one-to-one tutoring, and the recordings are a lifesaver when we have clubs on.', 4],
            ['Amelia R.', 'Year 10 student', 'Combined Science finally makes sense. The required practical walkthroughs were exactly what I needed.', 5],
        ];

        foreach ($testimonials as $position => [$name, $role, $content, $rating]) {
            Testimonial::firstOrCreate(
                ['author_name' => $name, 'author_role' => $role],
                ['content' => $content, 'rating' => $rating, 'is_verified' => true, 'is_published' => true, 'position' => $position],
            );
        }

        $faqs = [
            ['Getting started', 'Which year groups do you teach?', 'We teach Key Stage 3 (Years 7–9) and GCSE (Years 10–11) across Maths, English and Science.'],
            ['Getting started', 'Is there a free trial?', 'Yes. Every plan starts with a 7-day free trial, and you can cancel any time before it ends without being charged.'],
            ['Lessons', 'How do live lessons work?', 'Lessons are streamed live at a scheduled time. Your child logs in to their student portal and joins with one click. Recordings are available afterwards.'],
            ['Lessons', 'Will I be reminded before a session?', 'Yes. We email parents seven days before each session with preparation materials, and again two days before with joining instructions.'],
            ['Billing', 'Can I change or cancel my plan?', 'You can upgrade, downgrade or cancel from your parent dashboard at any time. Changes take effect from the next billing date.'],
            ['Billing', 'Do you offer sibling discounts?', 'Yes. Contact us for a sibling discount code, which you can apply at checkout.'],
            ['Safeguarding', 'Are your tutors DBS checked?', 'Every Marvel Academy tutor holds an enhanced DBS certificate and a recognised teaching qualification or degree in their subject.'],
        ];

        foreach ($faqs as $position => [$category, $question, $answer]) {
            Faq::firstOrCreate(
                ['question' => $question],
                ['category' => $category, 'answer' => $answer, 'is_published' => true, 'position' => $position],
            );
        }
    }
}
