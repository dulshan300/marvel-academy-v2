<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::portal')] #[Title('Teachers')] class extends Component
{
    /**
     * @return Collection<int, User>
     */
    #[Computed]
    public function teachers(): Collection
    {
        return User::role(Role::Teacher->value)
            ->with(['teacherProfile', 'taughtCourses'])
            ->withCount(['taughtSessions as upcoming_sessions_count' => fn ($query) => $query->where('starts_at', '>=', now())])
            ->orderBy('name')
            ->get();
    }
};
?>

<div class="space-y-6">
    <div class="flex justify-end">
        <a href="{{ route('admin.teachers.create') }}" wire:navigate class="btn-primary">Add teacher</a>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                <tr><th class="px-5 py-3">Teacher</th><th class="px-5 py-3">Courses</th><th class="px-5 py-3">Rating</th><th class="px-5 py-3">Upcoming</th><th class="px-5 py-3"><span class="sr-only">Actions</span></th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->teachers as $teacher)
                    <tr wire:key="teacher-{{ $teacher->id }}">
                        <td class="px-5 py-3">
                            <p class="font-semibold text-slate-800">{{ $teacher->name }} @unless ($teacher->is_active) <x-portal.badge tone="red">Deactivated</x-portal.badge> @endunless</p>
                            <p class="text-xs text-slate-500">{{ $teacher->teacherProfile?->headline ?? $teacher->email }}</p>
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $teacher->taughtCourses->map(fn ($course) => $course->key_stage->label().' '.$course->title)->join(', ') ?: '—' }}</td>
                        <td class="px-5 py-3">
                            @if ($teacher->teacherProfile?->rating_count)
                                <span class="font-semibold">{{ number_format((float) $teacher->teacherProfile->rating_average, 1) }}</span>
                                <span class="text-xs text-slate-500">({{ $teacher->teacherProfile->rating_count }})</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $teacher->upcoming_sessions_count }}</td>
                        <td class="px-5 py-3 text-right"><a href="{{ route('admin.teachers.edit', $teacher) }}" wire:navigate class="font-semibold text-brand-700">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-5 py-8 text-center text-slate-500">No teachers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
