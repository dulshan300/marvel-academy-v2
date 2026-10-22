@props(['rating', 'max' => 5])

<div {{ $attributes->class('flex items-center gap-0.5') }} role="img" aria-label="{{ $rating }} out of {{ $max }} stars">
    @for ($star = 1; $star <= $max; $star++)
        <svg @class(['size-4', 'text-accent-400' => $star <= round($rating), 'text-slate-300' => $star > round($rating)]) viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path d="M10 1.5l2.6 5.3 5.9.9-4.3 4.1 1 5.8L10 14.9l-5.2 2.7 1-5.8L1.5 7.7l5.9-.9L10 1.5Z"/>
        </svg>
    @endfor
</div>
