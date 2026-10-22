<?php

namespace Tests\Feature\Livewire;

use App\Enums\KeyStage;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CourseCatalogueTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_published_courses_only(): void
    {
        Course::factory()->create(['title' => 'Visible Maths']);
        Course::factory()->unpublished()->create(['title' => 'Hidden Draft']);

        $this->get(route('courses.index'))
            ->assertOk()
            ->assertSee('Visible Maths')
            ->assertDontSee('Hidden Draft');
    }

    public function test_filters_by_key_stage(): void
    {
        Course::factory()->keyStage(KeyStage::Ks3)->create(['title' => 'Lower School Maths']);
        Course::factory()->keyStage(KeyStage::Gcse)->create(['title' => 'Exam Year Maths']);

        Livewire::test('pages::courses.index')
            ->set('stage', KeyStage::Gcse->value)
            ->assertSee('Exam Year Maths')
            ->assertDontSee('Lower School Maths');
    }

    public function test_filters_by_subject_area(): void
    {
        Course::factory()->create(['title' => 'Poetry Course', 'subject_area' => 'English']);
        Course::factory()->create(['title' => 'Atoms Course', 'subject_area' => 'Science']);

        Livewire::test('pages::courses.index')
            ->set('subject', 'Science')
            ->assertSee('Atoms Course')
            ->assertDontSee('Poetry Course');
    }

    public function test_search_matches_title_case_insensitively(): void
    {
        Course::factory()->create(['title' => 'Combined Science', 'summary' => 'Physics, chemistry, biology.']);
        Course::factory()->create(['title' => 'English Language', 'summary' => 'Reading and writing.', 'subject_area' => 'English']);

        Livewire::test('pages::courses.index')
            ->set('search', 'combined')
            ->assertSee('Combined Science')
            ->assertDontSee('English Language');
    }

    public function test_unknown_key_stage_is_ignored(): void
    {
        Course::factory()->create(['title' => 'Any Course']);

        Livewire::withQueryParams(['stage' => 'not-a-stage'])
            ->test('pages::courses.index')
            ->assertSee('Any Course');
    }

    public function test_shows_empty_state_when_nothing_matches(): void
    {
        Course::factory()->create();

        Livewire::test('pages::courses.index')
            ->set('search', 'zzzz-nothing')
            ->assertSee('No courses match your search.');
    }

    public function test_clear_filters_resets_every_filter(): void
    {
        Livewire::test('pages::courses.index')
            ->set('search', 'maths')
            ->set('stage', KeyStage::Ks3->value)
            ->set('subject', 'Mathematics')
            ->call('clearFilters')
            ->assertSet('search', '')
            ->assertSet('stage', '')
            ->assertSet('subject', '');
    }
}
