<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Plan;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, string}>
     */
    public static function staticPages(): array
    {
        return [
            'how it works' => ['how-it-works', 'A learning routine that fits family life'],
            'about us' => ['about', 'Tutor expertise standards'],
            'terms' => ['legal.terms', 'Terms &amp; Conditions'],
            'privacy' => ['legal.privacy', 'Privacy Policy'],
        ];
    }

    #[DataProvider('staticPages')]
    public function test_static_page_renders(string $routeName, string $expectedText): void
    {
        $this->get(route($routeName))
            ->assertOk()
            ->assertSee($expectedText, escape: false);
    }

    public function test_home_page_shows_featured_courses_plans_and_published_testimonials(): void
    {
        $featured = Course::factory()->featured()->create(['title' => 'Featured Algebra']);
        $notFeatured = Course::factory()->create(['title' => 'Quiet Geography']);
        $plan = Plan::factory()->create(['name' => 'Achiever Plus', 'price_monthly' => 17900]);
        $published = Testimonial::factory()->create(['content' => 'Brilliant tutors and clear lessons.']);
        $hidden = Testimonial::factory()->create(['content' => 'Draft review text.', 'is_published' => false]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($featured->title)
            ->assertDontSee($notFeatured->title)
            ->assertSee($plan->name)
            ->assertSee('£179')
            ->assertSee($published->content)
            ->assertDontSee($hidden->content);
    }

    public function test_home_page_does_not_show_unpublished_featured_courses(): void
    {
        Course::factory()->featured()->unpublished()->create(['title' => 'Secret Draft Course']);

        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Secret Draft Course');
    }

    public function test_course_page_shows_published_course(): void
    {
        $course = Course::factory()->create(['title' => 'Combined Science', 'summary' => 'Trilogy coverage with practicals.']);

        $this->get(route('courses.show', $course))
            ->assertOk()
            ->assertSee('Trilogy coverage with practicals.');
    }

    public function test_course_page_returns_404_for_unpublished_course(): void
    {
        $course = Course::factory()->unpublished()->create();

        $this->get(route('courses.show', $course))->assertNotFound();
    }
}
