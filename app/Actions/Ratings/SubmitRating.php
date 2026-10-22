<?php

namespace App\Actions\Ratings;

use App\Models\Course;
use App\Models\LiveSession;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SubmitRating
{
    /**
     * Store a rating for the session's tutor and refresh the cached averages
     * on the tutor's profile and the course.
     */
    public function handle(User $rater, LiveSession $session, int $score, ?string $comment = null): Rating
    {
        return DB::transaction(function () use ($rater, $session, $score, $comment): Rating {
            $rating = Rating::create([
                'teacher_id' => $session->teacher_id,
                'rated_by_id' => $rater->id,
                'live_session_id' => $session->id,
                'score' => $score,
                'comment' => $comment !== null && trim($comment) !== '' ? trim($comment) : null,
            ]);

            $session->teacher?->teacherProfile?->refreshRatingAggregate();
            $this->refreshCourseRating($session->course);

            return $rating;
        });
    }

    private function refreshCourseRating(Course $course): void
    {
        $ratings = Rating::query()->whereIn('live_session_id', $course->liveSessions()->select('id'));

        $course->forceFill([
            'rating_average' => round((float) $ratings->avg('score'), 2),
            'rating_count' => $ratings->count(),
        ])->save();
    }
}
