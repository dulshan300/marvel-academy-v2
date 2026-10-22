<?php

namespace Tests\Feature\Models;

use App\Enums\KeyStage;
use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseTest extends TestCase
{
    use RefreshDatabase;

    public function test_slug_combines_key_stage_and_title(): void
    {
        $course = Course::factory()->keyStage(KeyStage::Gcse)->create(['title' => 'Combined Science']);

        $this->assertSame('gcse-combined-science', $course->slug);
    }

    public function test_same_title_in_another_key_stage_gets_its_own_slug(): void
    {
        $ks3 = Course::factory()->keyStage(KeyStage::Ks3)->create(['title' => 'Mathematics']);
        $gcse = Course::factory()->keyStage(KeyStage::Gcse)->create(['title' => 'Mathematics']);

        $this->assertSame('ks3-mathematics', $ks3->slug);
        $this->assertSame('gcse-mathematics', $gcse->slug);
    }

    public function test_published_scope_excludes_draft_courses(): void
    {
        $published = Course::factory()->create();
        Course::factory()->unpublished()->create();

        $this->assertEquals([$published->id], Course::published()->pluck('id')->all());
    }
}
