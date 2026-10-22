@if (session('status'))
    <div
        x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 6000)"
        x-transition
        role="status"
        class="container-page pt-6"
    >
        <div class="flex items-start justify-between gap-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <p>{{ session('status') }}</p>
            <button type="button" @click="show = false" class="font-semibold text-emerald-700 hover:text-emerald-900">
                <span class="sr-only">Dismiss</span>&times;
            </button>
        </div>
    </div>
@endif
