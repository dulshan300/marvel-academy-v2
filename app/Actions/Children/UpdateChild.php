<?php

namespace App\Actions\Children;

use App\Enums\EnrolmentStatus;
use App\Models\Child;
use Illuminate\Support\Facades\DB;

class UpdateChild
{
    /**
     * Update the child's details and keep their student login in step.
     *
     * Moving the child into a different key stage cancels their courses from
     * the old key stage, since those no longer match the year they are in.
     * Returns whether the key stage changed.
     *
     * @param  array{first_name: string, last_name: string, date_of_birth?: ?string, year_group: int, school_name?: ?string, student_email?: ?string, student_password?: ?string}  $data
     */
    public function handle(Child $child, array $data): bool
    {
        return DB::transaction(function () use ($child, $data): bool {
            $keyStage = Child::keyStageForYear($data['year_group']);
            $keyStageChanged = $keyStage !== $child->key_stage;

            $child->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'year_group' => $data['year_group'],
                'key_stage' => $keyStage,
                'school_name' => $data['school_name'] ?? null,
            ]);

            if ($keyStageChanged) {
                $child->enrolments()
                    ->where('status', EnrolmentStatus::Active)
                    ->whereHas('course', fn ($query) => $query->where('key_stage', '!=', $keyStage))
                    ->update(['status' => EnrolmentStatus::Cancelled]);
            }

            if ($child->student !== null) {
                $child->student->name = trim($data['first_name'].' '.$data['last_name']);

                if (! empty($data['student_email'])) {
                    $child->student->email = mb_strtolower($data['student_email']);
                }

                if (! empty($data['student_password'])) {
                    $child->student->password = $data['student_password'];
                }

                $child->student->save();
            }

            return $keyStageChanged;
        });
    }
}
