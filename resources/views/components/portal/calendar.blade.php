@props([
    'month',
    'sessions',
    'link',
])

{{--
    Month grid of live sessions. $month is any date in the month to show,
    $sessions a collection of LiveSession models, and $link a closure that
    returns the URL for a session.
--}}

@php
    $start = $month->copy()->startOfMonth()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
    $end = $month->copy()->endOfMonth()->endOfWeek(\Carbon\CarbonInterface::SUNDAY);
    $byDay = $sessions->groupBy(fn ($session) => $session->starts_at->toDateString());
@endphp

<div {{ $attributes->class('overflow-hidden rounded-2xl border border-slate-200 bg-white') }}>
    <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-semibold tracking-wide text-slate-500 uppercase">
        @foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
            <div class="py-2">{{ $weekday }}</div>
        @endforeach
    </div>
    <div class="grid grid-cols-7">
        @for ($day = $start->copy(); $day->lte($end); $day->addDay())
            @php($daySessions = $byDay->get($day->toDateString(), collect()))
            <div @class([
                'min-h-24 border-b border-r border-slate-100 p-1.5 text-xs sm:min-h-28',
                'bg-slate-50/60 text-slate-400' => ! $day->isSameMonth($month),
            ])>
                <p @class([
                    'mb-1 flex size-6 items-center justify-center rounded-full font-semibold',
                    'bg-brand-700 text-white' => $day->isToday(),
                ])>{{ $day->day }}</p>
                <ul class="space-y-1">
                    @foreach ($daySessions as $session)
                        <li>
                            <a href="{{ $link($session) }}" wire:navigate @class([
                                'block truncate rounded-md px-1.5 py-1 font-medium',
                                'bg-red-50 text-red-700 line-through' => $session->status === \App\Enums\LiveSessionStatus::Cancelled,
                                'bg-brand-50 text-brand-800 hover:bg-brand-100' => $session->status !== \App\Enums\LiveSessionStatus::Cancelled,
                            ]) title="{{ $session->title }}">
                                {{ $session->starts_at->format('H:i') }} {{ $session->title }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endfor
    </div>
</div>
