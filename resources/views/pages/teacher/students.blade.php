<?php

use App\Enums\EnrolmentStatus;
use App\Models\Child;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::portal')] #[Title('My students')] class extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $course = '';

    public function updatedCourse(): void
    {
        $this->resetPage();
    }

    /**
     * @return Collection<int, \App\Models\Course>
     */
    #[Computed]
    public function courses(): Collection
    {
        return auth()->user()->taughtCourses()->orderBy('key_stage')->orderBy('title')->get();
    }

    #[Computed]
    public function students(): LengthAwarePaginator
    {
        $courseIds = $this->course !== '' && $this->courses->contains('id', (int) $this->course)
            ? [(int) $this->course]
            : $this->courses->modelKeys();

        return Child::query()
            ->whereHas('enrolments', fn ($query) => $query->whereIn('course_id', $courseIds)->where('status', EnrolmentStatus::Active))
            ->with(['activeCourses' => fn ($query) => $query->whereIn('courses.id', $courseIds)])
            ->withAvg(['quizAttempts as average_score' => fn ($query) => $query->whereNotNull('completed_at')], 'percentage')
            ->withCount(['quizAttempts as quizzes_taken' => fn ($query) => $query->whereNotNull('completed_at')])
            ->withCount(['attendances as sessions_attended' => fn ($query) => $query->whereHas('liveSession', fn ($session) => $session->whereIn('course_id', $courseIds))])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(25);
    }
};
?>

<div class="space-y-6">
    <div class="flex justify-end">
        <select wire:model.live="course" class="form-input max-w-xs" aria-label="Filter by course">
            <option value="">All my courses</option>
            @foreach ($this->courses as $option)
                <option value="{{ $option->id }}">{{ $option->key_stage->label() }} {{ $option->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="overflow-x-auto rounded-2xl border border-slate-200 bg-white shadow-sm">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-slate-200 bg-slate-50 text-xs text-slate-500 uppercase">
                <tr><th class="px-5 py-3">Student</th><th class="px-5 py-3">Year</th><th class="px-5 py-3">Courses</th><th class="px-5 py-3">Quizzes</th><th class="px-5 py-3">Average</th><th class="px-5 py-3">Sessions attended</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($this->students as $child)
                    <tr wire:key="student-{{ $child->id }}">
                        <td class="px-5 py-3 font-medium text-slate-800">{{ $child->full_name }}</td>
                        <td class="px-5 py-3 text-slate-600">Year {{ $child->year_group }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $child->activeCourses->map(fn ($course) => $course->title)->join(', ') }}</td>
                        <td class="px-5 py-3 text-slate-600">{{ $child->quizzes_taken }}</td>
                        <td class="px-5 py-3">
                            @if ($child->average_score !== null)
                                <x-portal.badge :tone="$child->average_score >= 60 ? 'green' : 'amber'">{{ round($child->average_score) }}%</x-portal.badge>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-slate-600">{{ $child->sessions_attended }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-slate-500">No students enrolled on your courses yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{ $this->students->links() }}
</div>
