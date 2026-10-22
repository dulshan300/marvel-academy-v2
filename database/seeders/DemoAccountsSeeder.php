<?php

namespace Database\Seeders;

use App\Billing\LocalBillingGateway;
use App\Enums\EnrolmentStatus;
use App\Enums\KeyStage;
use App\Enums\Role;
use App\Models\Child;
use App\Models\Course;
use App\Models\LiveSession;
use App\Models\Plan;
use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Seed one login per portal (password: "password") plus teachers and
     * upcoming sessions so every dashboard has data locally.
     */
    public function run(): void
    {
        $this->user('admin@marvelacademy.test', 'Alex Admin', Role::Admin);

        $teachers = collect([
            ['teacher@marvelacademy.test', 'Hannah Wright', 'Mathematics Tutor', 'BEng Civil Engineering', 'Mathematics'],
            ['olu.adeyemi@marvelacademy.test', 'Olu Adeyemi', 'Biology Tutor', 'BSc Biomedical Science', 'Biology'],
            ['mei.chen@marvelacademy.test', 'Dr Mei Chen', 'Chemistry Tutor', 'MEng Chemical Engineering', 'Chemistry'],
            ['tom.okafor@marvelacademy.test', 'Tom Okafor', 'Physics Tutor', 'MPhys Physics', 'Physics'],
        ])->map(function (array $teacher): User {
            [$email, $name, $headline, $qualifications, $subjectArea] = $teacher;

            $user = $this->user($email, $name, Role::Teacher);

            TeacherProfile::updateOrCreate(['user_id' => $user->id], [
                'headline' => $headline,
                'bio' => "{$name} has helped hundreds of students across the UK reach their target grades in {$subjectArea}.",
                'qualifications' => $qualifications,
                'years_experience' => 12,
                'dbs_checked' => true,
                'is_featured' => true,
            ]);

            Course::where('subject_area', $subjectArea)->each(
                fn (Course $course) => $course->teachers()->syncWithoutDetaching([$user->id])
            );

            return $user;
        });

        $parent = $this->user('parent@marvelacademy.test', 'Priya Sharma', Role::Parent);
        $student = $this->user('student@marvelacademy.test', 'Aarav Sharma', Role::Student);

        $child = Child::updateOrCreate(['student_id' => $student->id], [
            'parent_id' => $parent->id,
            'first_name' => 'Aarav',
            'last_name' => 'Sharma',
            'date_of_birth' => now()->subYears(15)->toDateString(),
            'key_stage' => KeyStage::Gcse,
            'year_group' => 10,
            'school_name' => 'Riverside Academy',
        ]);

        $gcseCourses = Course::where('key_stage', KeyStage::Gcse)->whereIn('subject_area', ['Mathematics', 'Biology'])->get();

        foreach ($gcseCourses as $course) {
            $child->courses()->syncWithoutDetaching([
                $course->id => ['status' => EnrolmentStatus::Active->value, 'enrolled_at' => now()],
            ]);

            if ($course->liveSessions()->exists()) {
                continue;
            }

            $teacher = $course->teachers()->first() ?? $teachers->first();
            $topic = $course->topics()->first();

            // Classes run on alternate Saturdays in 2.5-hour slots.
            $firstSaturday = now()->next(Carbon::SATURDAY);
            $startHour = $course->subject_area === 'Mathematics' ? 10 : 13;

            foreach ([0, 2, 4] as $fortnight) {
                LiveSession::factory()->for($course)->create([
                    'topic_id' => $topic?->id,
                    'teacher_id' => $teacher->id,
                    'title' => Str::limit("{$course->title}: {$topic?->title}", 80),
                    'starts_at' => $firstSaturday->copy()->addWeeks($fortnight)->setTime($startHour, 0),
                    'duration_minutes' => 150,
                ]);
            }

            LiveSession::factory()->completed()->for($course)->create([
                'topic_id' => $topic?->id,
                'teacher_id' => $teacher->id,
                'title' => "{$course->title}: introduction",
            ]);
        }

        $plan = Plan::where('slug', 'two-subjects')->first();
        $child->plan()->associate($plan)->save();

        if ($plan !== null && config('services.billing.driver') === 'local' && ! $child->hasActiveSubscription()) {
            app(LocalBillingGateway::class)->startSubscription($child, $plan, null, '/', '/');
        }
    }

    private function user(string $email, string $name, Role $role): User
    {
        $user = User::firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => 'password',
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$role->value]);

        return $user;
    }
}
