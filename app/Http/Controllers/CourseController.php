<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function show(Course $course): View
    {
        abort_unless($course->is_published, 404);

        $course->load([
            'media',
            'subjects.topics' => fn ($query) => $query->withCount('quizzes'),
            'teachers.teacherProfile.media',
        ]);

        return view('courses.show', [
            'course' => $course,
            'upcomingSessions' => $course->liveSessions()->upcoming()->limit(3)->get(),
            'relatedCourses' => Course::published()
                ->with('media')
                ->where('key_stage', $course->key_stage)
                ->whereKeyNot($course->getKey())
                ->orderBy('title')
                ->limit(3)
                ->get(),
        ]);
    }
}
