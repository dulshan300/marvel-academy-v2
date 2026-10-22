<?php

use App\Enums\Permission;
use App\Models\ContactMessage;
use App\Models\Faq;
use App\Models\NewsletterSubscriber;
use App\Models\Testimonial;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::portal')] #[Title('Website content')] class extends Component
{
    use WithPagination;

    #[Url]
    public string $tab = 'messages';

    public ?int $openMessageId = null;

    public string $testimonialAuthor = '';

    public string $testimonialRole = '';

    public string $testimonialContent = '';

    public int $testimonialRating = 5;

    public bool $testimonialVerified = false;

    public string $faqCategory = 'General';

    public string $faqQuestion = '';

    public string $faqAnswer = '';

    #[Computed]
    public function messages(): LengthAwarePaginator
    {
        return ContactMessage::latest()->paginate(15, pageName: 'messages');
    }

    /**
     * @return Collection<int, Testimonial>
     */
    #[Computed]
    public function testimonials(): Collection
    {
        return Testimonial::orderBy('position')->orderBy('id')->get();
    }

    /**
     * @return Collection<int, Faq>
     */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::orderBy('position')->orderBy('id')->get();
    }

    /**
     * @return array{active: int, unsubscribed: int}
     */
    #[Computed]
    public function newsletterCounts(): array
    {
        return [
            'active' => NewsletterSubscriber::whereNull('unsubscribed_at')->count(),
            'unsubscribed' => NewsletterSubscriber::whereNotNull('unsubscribed_at')->count(),
        ];
    }

    public function openMessage(int $messageId): void
    {
        $this->authorize(Permission::ManageContent->value);

        $message = ContactMessage::findOrFail($messageId);
        $message->read_at ??= now();
        $message->save();

        $this->openMessageId = $this->openMessageId === $messageId ? null : $messageId;
        unset($this->messages);
    }

    public function deleteMessage(int $messageId): void
    {
        $this->authorize(Permission::ManageContent->value);

        ContactMessage::findOrFail($messageId)->delete();
        unset($this->messages);
    }

    public function addTestimonial(): void
    {
        $this->authorize(Permission::ManageContent->value);

        $validated = $this->validate([
            'testimonialAuthor' => ['required', 'string', 'max:120'],
            'testimonialRole' => ['required', 'string', 'max:64'],
            'testimonialContent' => ['required', 'string', 'max:1000'],
            'testimonialRating' => ['required', 'integer', 'between:1,5'],
            'testimonialVerified' => ['boolean'],
        ]);

        Testimonial::create([
            'author_name' => $validated['testimonialAuthor'],
            'author_role' => $validated['testimonialRole'],
            'content' => $validated['testimonialContent'],
            'rating' => $validated['testimonialRating'],
            'is_verified' => $validated['testimonialVerified'],
            'is_published' => true,
            'position' => (int) Testimonial::max('position') + 1,
        ]);

        $this->reset('testimonialAuthor', 'testimonialRole', 'testimonialContent', 'testimonialRating', 'testimonialVerified');
        unset($this->testimonials);
    }

    public function toggleTestimonial(int $testimonialId, string $field): void
    {
        $this->authorize(Permission::ManageContent->value);

        abort_unless(in_array($field, ['is_published', 'is_verified'], true), 422);

        $testimonial = Testimonial::findOrFail($testimonialId);
        $testimonial->update([$field => ! $testimonial->{$field}]);
        unset($this->testimonials);
    }

    public function deleteTestimonial(int $testimonialId): void
    {
        $this->authorize(Permission::ManageContent->value);

        Testimonial::findOrFail($testimonialId)->delete();
        unset($this->testimonials);
    }

    public function addFaq(): void
    {
        $this->authorize(Permission::ManageContent->value);

        $validated = $this->validate([
            'faqCategory' => ['required', 'string', 'max:64'],
            'faqQuestion' => ['required', 'string', 'max:255'],
            'faqAnswer' => ['required', 'string', 'max:2000'],
        ]);

        Faq::create([
            'category' => $validated['faqCategory'],
            'question' => $validated['faqQuestion'],
            'answer' => $validated['faqAnswer'],
            'is_published' => true,
            'position' => (int) Faq::max('position') + 1,
        ]);

        $this->reset('faqQuestion', 'faqAnswer');
        unset($this->faqs);
    }

    public function toggleFaq(int $faqId): void
    {
        $this->authorize(Permission::ManageContent->value);

        $faq = Faq::findOrFail($faqId);
        $faq->update(['is_published' => ! $faq->is_published]);
        unset($this->faqs);
    }

    public function deleteFaq(int $faqId): void
    {
        $this->authorize(Permission::ManageContent->value);

        Faq::findOrFail($faqId)->delete();
        unset($this->faqs);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap gap-2" role="tablist">
        @foreach (['messages' => 'Enquiries', 'testimonials' => 'Testimonials', 'faqs' => 'FAQs', 'newsletter' => 'Newsletter'] as $key => $label)
            <button type="button" role="tab" wire:click="$set('tab', '{{ $key }}')" :aria-selected="@js($tab === $key)" @class([
                'rounded-full px-4 py-2 text-sm font-semibold',
                'bg-brand-700 text-white' => $tab === $key,
                'bg-white text-slate-600 ring-1 ring-slate-200 hover:text-brand-700' => $tab !== $key,
            ])>{{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'messages')
        <x-portal.card title="Contact form enquiries">
            <ul class="divide-y divide-slate-100">
                @forelse ($this->messages as $message)
                    <li wire:key="message-{{ $message->id }}" class="py-3">
                        <div class="flex items-center justify-between gap-4">
                            <button type="button" wire:click="openMessage({{ $message->id }})" class="min-w-0 flex-1 text-left">
                                <p class="truncate text-sm {{ $message->read_at ? 'text-slate-700' : 'font-bold text-slate-900' }}">{{ $message->subject }}</p>
                                <p class="text-xs text-slate-500">{{ $message->name }} · {{ $message->email }} · {{ $message->created_at->diffForHumans() }}</p>
                            </button>
                            @unless ($message->read_at) <x-portal.badge tone="brand">New</x-portal.badge> @endunless
                        </div>
                        @if ($openMessageId === $message->id)
                            <div class="mt-3 rounded-xl bg-slate-50 p-4 text-sm">
                                <p class="whitespace-pre-line text-slate-700">{{ $message->message }}</p>
                                <div class="mt-4 flex gap-4">
                                    <a href="mailto:{{ $message->email }}?subject=Re: {{ rawurlencode($message->subject) }}" class="font-semibold text-brand-700">Reply by email</a>
                                    @if ($message->phone) <a href="tel:{{ $message->phone }}" class="font-semibold text-brand-700">{{ $message->phone }}</a> @endif
                                    <button type="button" wire:click="deleteMessage({{ $message->id }})" wire:confirm="Delete this enquiry?" class="ml-auto text-red-600">Delete</button>
                                </div>
                            </div>
                        @endif
                    </li>
                @empty
                    <li class="py-3 text-sm text-slate-500">No enquiries yet.</li>
                @endforelse
            </ul>
            <div class="mt-4">{{ $this->messages->links() }}</div>
        </x-portal.card>
    @elseif ($tab === 'testimonials')
        <div class="grid gap-6 xl:grid-cols-3">
            <x-portal.card title="Testimonials" class="xl:col-span-2">
                <ul class="divide-y divide-slate-100">
                    @foreach ($this->testimonials as $testimonial)
                        <li wire:key="testimonial-{{ $testimonial->id }}" class="py-3">
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="text-sm text-slate-700">&ldquo;{{ Str::limit($testimonial->content, 160) }}&rdquo;</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $testimonial->author_name }} · {{ $testimonial->author_role }} · {{ $testimonial->rating }}★</p>
                                </div>
                                <div class="flex shrink-0 items-center gap-2 text-xs">
                                    <button type="button" wire:click="toggleTestimonial({{ $testimonial->id }}, 'is_verified')"><x-portal.badge :tone="$testimonial->is_verified ? 'green' : 'slate'">{{ $testimonial->is_verified ? 'Verified' : 'Unverified' }}</x-portal.badge></button>
                                    <button type="button" wire:click="toggleTestimonial({{ $testimonial->id }}, 'is_published')"><x-portal.badge :tone="$testimonial->is_published ? 'brand' : 'slate'">{{ $testimonial->is_published ? 'Shown' : 'Hidden' }}</x-portal.badge></button>
                                    <button type="button" wire:click="deleteTestimonial({{ $testimonial->id }})" wire:confirm="Delete this testimonial?" class="text-red-600">✕</button>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-portal.card>
            <x-portal.card title="Add testimonial">
                <form wire:submit="addTestimonial" class="space-y-3">
                    <input type="text" wire:model="testimonialAuthor" placeholder="Name, e.g. Sarah P." class="form-input">
                    @error('testimonialAuthor') <p class="form-error">{{ $message }}</p> @enderror
                    <input type="text" wire:model="testimonialRole" placeholder="e.g. Parent of a Year 10 student" class="form-input">
                    @error('testimonialRole') <p class="form-error">{{ $message }}</p> @enderror
                    <textarea wire:model="testimonialContent" rows="4" placeholder="Quote" class="form-input"></textarea>
                    @error('testimonialContent') <p class="form-error">{{ $message }}</p> @enderror
                    <select wire:model="testimonialRating" class="form-input" aria-label="Rating">
                        @foreach ([5, 4, 3, 2, 1] as $stars) <option value="{{ $stars }}">{{ $stars }} stars</option> @endforeach
                    </select>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="testimonialVerified" class="size-4 rounded border-slate-300 text-brand-700"> Verified family</label>
                    <button type="submit" class="btn-secondary w-full">Add testimonial</button>
                </form>
            </x-portal.card>
        </div>
    @elseif ($tab === 'faqs')
        <div class="grid gap-6 xl:grid-cols-3">
            <x-portal.card title="FAQs" class="xl:col-span-2">
                <ul class="divide-y divide-slate-100">
                    @foreach ($this->faqs as $faq)
                        <li wire:key="faq-{{ $faq->id }}" class="flex items-start justify-between gap-4 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-800">{{ $faq->question }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $faq->category }} · {{ Str::limit($faq->answer, 120) }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-2 text-xs">
                                <button type="button" wire:click="toggleFaq({{ $faq->id }})"><x-portal.badge :tone="$faq->is_published ? 'brand' : 'slate'">{{ $faq->is_published ? 'Shown' : 'Hidden' }}</x-portal.badge></button>
                                <button type="button" wire:click="deleteFaq({{ $faq->id }})" wire:confirm="Delete this FAQ?" class="text-red-600">✕</button>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </x-portal.card>
            <x-portal.card title="Add FAQ">
                <form wire:submit="addFaq" class="space-y-3">
                    <input type="text" wire:model="faqCategory" placeholder="Category" class="form-input">
                    @error('faqCategory') <p class="form-error">{{ $message }}</p> @enderror
                    <input type="text" wire:model="faqQuestion" placeholder="Question" class="form-input">
                    @error('faqQuestion') <p class="form-error">{{ $message }}</p> @enderror
                    <textarea wire:model="faqAnswer" rows="4" placeholder="Answer" class="form-input"></textarea>
                    @error('faqAnswer') <p class="form-error">{{ $message }}</p> @enderror
                    <button type="submit" class="btn-secondary w-full">Add FAQ</button>
                </form>
            </x-portal.card>
        </div>
    @else
        <x-portal.card title="Newsletter subscribers">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div class="rounded-xl bg-slate-50 p-4"><dt class="text-sm text-slate-500">Subscribed</dt><dd class="font-display text-3xl font-extrabold text-brand-950">{{ $this->newsletterCounts['active'] }}</dd></div>
                <div class="rounded-xl bg-slate-50 p-4"><dt class="text-sm text-slate-500">Unsubscribed</dt><dd class="font-display text-3xl font-extrabold text-brand-950">{{ $this->newsletterCounts['unsubscribed'] }}</dd></div>
            </dl>
            <a href="{{ route('admin.newsletter.export') }}" class="btn-secondary mt-6">Download CSV of subscribers</a>
        </x-portal.card>
    @endif
</div>
