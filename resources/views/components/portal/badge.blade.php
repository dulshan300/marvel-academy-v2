@props(['tone' => 'slate'])

<span {{ $attributes->class([
    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold',
    'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' => $tone === 'green',
    'bg-amber-50 text-amber-700 ring-1 ring-amber-200' => $tone === 'amber',
    'bg-red-50 text-red-700 ring-1 ring-red-200' => $tone === 'red',
    'bg-brand-50 text-brand-700 ring-1 ring-brand-200' => $tone === 'brand',
    'bg-slate-100 text-slate-600 ring-1 ring-slate-200' => $tone === 'slate',
]) }}>{{ $slot }}</span>
