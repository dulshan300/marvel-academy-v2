@use('App\Enums\Permission')
@use('App\Enums\Role')

@php
    $items = match (auth()->user()->primaryRole()) {
        Role::Parent => [
            ['Dashboard', 'parent.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['Add a child', 'parent.children.create', 'M12 4v16m8-8H4'],
            ['Billing & plans', 'parent.billing', 'M3 7h18M3 7v10a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V7M3 7l2-3h14l2 3M7 15h4'],
        ],
        Role::Student => [
            ['My learning', 'student.dashboard', 'M12 6.25v13m0-13C10.8 5.5 9.2 5 7.5 5S4.2 5.5 3 6.25v13C4.2 18.5 5.8 18 7.5 18s3.3.5 4.5 1.25m0-13C13.2 5.5 14.8 5 16.5 5s3.3.5 4.5 1.25v13C19.8 18.5 18.2 18 16.5 18s-3.3.5-4.5 1.25'],
            ['Timetable', 'student.timetable', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z'],
        ],
        Role::Teacher => [
            ['Dashboard', 'teacher.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10'],
            ['Schedule', 'teacher.schedule', 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z'],
            ['My students', 'teacher.students', 'M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.28-.36-1.86M7 20H2v-2a3 3 0 0 1 5.36-1.86M7 20v-2c0-.66.13-1.28.36-1.86m0 0a5 5 0 0 1 9.28 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
            ['My profile', 'teacher.profile', 'M16 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0ZM12 14a7 7 0 0 0-7 7h14a7 7 0 0 0-7-7Z'],
        ],
        default => [],
    };

    if (Gate::allows('access-admin')) {
        $items = collect([
            ['Dashboard', 'admin.dashboard', 'M3 12l9-9 9 9M5 10v10h14V10', null],
            ['Courses', 'admin.courses.index', 'M12 6.25v13m0-13C10.8 5.5 9.2 5 7.5 5S4.2 5.5 3 6.25v13C4.2 18.5 5.8 18 7.5 18s3.3.5 4.5 1.25m0-13C13.2 5.5 14.8 5 16.5 5s3.3.5 4.5 1.25v13C19.8 18.5 18.2 18 16.5 18s-3.3.5-4.5 1.25', Permission::ManageCourses],
            ['Live sessions', 'admin.sessions.index', 'M15 10l4.55-2.28A1 1 0 0 1 21 8.62v6.76a1 1 0 0 1-1.45.9L15 14M5 18h8a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2Z', Permission::ManageLiveSessions],
            ['Families', 'admin.families.index', 'M17 20h5v-2a3 3 0 0 0-5.36-1.86M17 20H7m10 0v-2c0-.66-.13-1.28-.36-1.86M7 20H2v-2a3 3 0 0 1 5.36-1.86M7 20v-2c0-.66.13-1.28.36-1.86m0 0a5 5 0 0 1 9.28 0M15 7a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z', Permission::ManageFamilies],
            ['Teachers', 'admin.teachers.index', 'M12 14l9-5-9-5-9 5 9 5Zm0 0v6m-6-9.5v5c0 1.5 2.7 3 6 3s6-1.5 6-3v-5', Permission::ManageTeachers],
            ['Payments', 'admin.payments.index', 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 0 0 3-3V8a3 3 0 0 0-3-3H6a3 3 0 0 0-3 3v8a3 3 0 0 0 3 3Z', Permission::ManagePayments],
            ['Website content', 'admin.content.index', 'M7 8h10M7 12h4m1 8-4-4H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2h-3l-4 4Z', Permission::ManageContent],
            ['Emails', 'admin.emails.index', 'M3 8l7.9 5.26a2 2 0 0 0 2.2 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z', Permission::ManageEmails],
            ['Roles & permissions', 'admin.roles.index', 'M9 12l2 2 4-4m5.62-4.02A11.96 11.96 0 0 1 12 2.94a11.96 11.96 0 0 1-8.62 3.04A12 12 0 0 0 3 9c0 5.6 3.82 10.3 9 11.62 5.18-1.33 9-6.03 9-11.62 0-1.04-.13-2.05-.38-3.02Z', Permission::ManageRoles],
            ['Backups', 'admin.backups.index', 'M4 7v10c0 2.2 3.6 4 8 4s8-1.8 8-4V7M4 7c0 2.2 3.6 4 8 4s8-1.8 8-4M4 7c0-2.2 3.6-4 8-4s8 1.8 8 4m0 5c0 2.2-3.6 4-8 4s-8-1.8-8-4', Permission::ManageBackups],
        ])
            ->filter(fn (array $item) => $item[3] === null || auth()->user()->can($item[3]->value))
            ->map(fn (array $item) => array_slice($item, 0, 3))
            ->all();
    }
@endphp

<nav {{ $attributes }} aria-label="Portal">
    <ul class="space-y-1">
        @foreach ($items as [$label, $route, $icon])
            @continue(! Route::has($route))
            <li>
                <a
                    href="{{ route($route) }}"
                    wire:navigate
                    @class([
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition',
                        'bg-white/10 text-white' => request()->routeIs($route),
                        'text-brand-200 hover:bg-white/5 hover:text-white' => ! request()->routeIs($route),
                    ])
                    @if (request()->routeIs($route)) aria-current="page" @endif
                >
                    <svg class="size-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                    {{ $label }}
                </a>
            </li>
        @endforeach
    </ul>
</nav>
