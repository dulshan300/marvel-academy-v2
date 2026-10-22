<?php

use App\Enums\KeyStage;
use App\Models\Course;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Courses')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $stage = '';

    #[Url(except: '')]
    public string $subject = '';

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'stage', 'subject'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'stage', 'subject');
        $this->resetPage();
    }

    /**
     * @return Collection<int, string>
     */
    #[Computed(persist: true, seconds: 600)]
    public function subjectAreas(): Collection
    {
        return Course::published()->distinct()->orderBy('subject_area')->pluck('subject_area');
    }

    #[Computed]
    public function courses(): LengthAwarePaginator
    {
        $keyStage = KeyStage::tryFrom($this->stage);
        $search = trim($this->search);

        return Course::published()
            ->with('media')
            ->when($keyStage, fn ($query) => $query->where('key_stage', $keyStage))
            ->when($this->subject !== '', fn ($query) => $query->where('subject_area', $this->subject))
            ->when($search !== '', function ($query) use ($search) {
                $term = '%'.mb_strtolower($search).'%';

                $query->where(fn ($query) => $query
                    ->whereRaw('LOWER(title) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(summary) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(subject_area) LIKE ?', [$term]));
            })
            ->orderByDesc('is_featured')
            ->orderBy('key_stage')
            ->orderBy('title')
            ->orderBy('id')
            ->paginate(9);
    }
};
?>

<div>
    <section class="bg-brand-950 py-16 text-white lg:py-20">
        <div class="container-page">
            <p class="text-sm font-semibold tracking-wide text-accent-400 uppercase">Courses</p>
            <h1 class="mt-2 text-4xl font-extrabold sm:text-5xl">Find the right course</h1>
            <p class="mt-4 max-w-2xl text-lg text-brand-100">
                Live, curriculum-aligned courses for Key Stage 3 and GCSE. Every course includes recorded lessons, downloadable materials and topic quizzes.
            </p>
        </div>
    </section>

    <section class="py-12 lg:py-16">
        <div class="container-page">
            <form class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-12" role="search" wire:submit.prevent>
                <div class="md:col-span-6">
                    <label for="course-search" class="sr-only">Search courses</label>
                    <input id="course-search" type="search" wire:model.live.debounce.350ms="search" placeholder="Search by subject or topic…" class="form-input">
                </div>
                <div class="md:col-span-3">
                    <label for="course-stage" class="sr-only">Key stage</label>
                    <select id="course-stage" wire:model.live="stage" class="form-input">
                        <option value="">All key stages</option>
                        @foreach ([\App\Enums\KeyStage::Ks3, \App\Enums\KeyStage::Gcse] as $keyStage)
                            <option value="{{ $keyStage->value }}">{{ $keyStage->label() }} ({{ $keyStage->yearRange() }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label for="course-subject" class="sr-only">Subject</label>
                    <select id="course-subject" wire:model.live="subject" class="form-input">
                        <option value="">All subjects</option>
                        @foreach ($this->subjectAreas as $area)
                            <option value="{{ $area }}">{{ $area }}</option>
                        @endforeach
                    </select>
                </div>
            </form>

            <div class="mt-8 flex items-center justify-between text-sm text-slate-500">
                <p aria-live="polite">
                    {{ $this->courses->total() }} {{ Str::plural('course', $this->courses->total()) }} found
                </p>
                @if ($search !== '' || $stage !== '' || $subject !== '')
                    <button type="button" wire:click="clearFilters" class="font-semibold text-brand-700 hover:text-brand-900">Clear filters</button>
                @endif
            </div>

            <div class="mt-6 grid gap-6 transition-opacity sm:grid-cols-2 lg:grid-cols-3" wire:loading.class="opacity-60">
                @forelse ($this->courses as $course)
                    <x-site.course-card :course="$course" wire:key="course-{{ $course->id }}" />
                @empty
                    <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-12 text-center">
                        <p class="font-semibold text-slate-700">No courses match your search.</p>
                        <p class="mt-1 text-sm text-slate-500">Try a different subject or key stage.</p>
                    </div>
                @endforelse
            </div>

            <div class="mt-10">
                {{ $this->courses->links() }}
            </div>
        </div>
    </section>
</div>
