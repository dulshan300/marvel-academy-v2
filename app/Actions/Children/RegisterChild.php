<?php

namespace App\Actions\Children;

use App\Enums\Role;
use App\Models\Child;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterChild
{
    /**
     * Create a child for the parent together with their student portal login.
     *
     * @param  array{first_name: string, last_name: string, date_of_birth?: ?string, year_group: int, school_name?: ?string, student_email: string, student_password: string}  $data
     */
    public function handle(User $parent, array $data): Child
    {
        return DB::transaction(function () use ($parent, $data): Child {
            $student = User::create([
                'name' => trim($data['first_name'].' '.$data['last_name']),
                'email' => mb_strtolower($data['student_email']),
                'password' => $data['student_password'],
            ]);

            $student->forceFill(['email_verified_at' => now()])->save();
            $student->assignRole(Role::Student->value);

            return $parent->children()->create([
                'student_id' => $student->id,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'year_group' => $data['year_group'],
                'key_stage' => Child::keyStageForYear($data['year_group']),
                'school_name' => $data['school_name'] ?? null,
            ]);
        });
    }
}
