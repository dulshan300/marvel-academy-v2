<?php

namespace App\Actions\Children;

use App\Enums\EnrolmentStatus;
use App\Models\Child;
use App\Models\Course;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignCourses
{
    /**
     * Replace the child's active courses with the given selection, enforcing
     * the plan's subject allowance and the child's key stage.
     *
     * @param  array<int, int>  $courseIds
     *
     * @throws ValidationException
     */
    public function handle(Child $child, Plan $plan, array $courseIds, string $errorKey = 'courses'): void
    {
        $courseIds = array_values(array_unique(array_map('intval', $courseIds)));

        if ($courseIds === []) {
            throw ValidationException::withMessages([$errorKey => 'Choose at least one course.']);
        }

        if (count($courseIds) > $plan->subjects_included) {
            throw ValidationException::withMessages([
                $errorKey => "The {$plan->name} plan includes up to {$plan->subjects_included} ".str('course')->plural($plan->subjects_included).'.',
            ]);
        }

        $eligibleIds = Course::published()
            ->where('key_stage', $child->key_stage)
            ->whereKey($courseIds)
            ->pluck('id');

        if ($eligibleIds->count() !== count($courseIds)) {
            throw ValidationException::withMessages([
                $errorKey => "Choose courses for {$child->first_name}'s key stage ({$child->key_stage->label()}).",
            ]);
        }

        DB::transaction(function () use ($child, $plan, $eligibleIds): void {
            $child->plan()->associate($plan)->save();

            $child->enrolments()
                ->whereNotIn('course_id', $eligibleIds)
                ->update(['status' => EnrolmentStatus::Cancelled]);

            foreach ($eligibleIds as $courseId) {
                $child->enrolments()->updateOrCreate(
                    ['course_id' => $courseId],
                    ['status' => EnrolmentStatus::Active, 'enrolled_at' => now()],
                );
            }
        });
    }
}
