@props(['label', 'value', 'icon' => null, 'accent' => 'indigo'])

@php
    $accents = [
        'indigo' => 'bg-indigo-50 text-indigo-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'rose' => 'bg-rose-50 text-rose-600',
    ];
@endphp

<div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="flex items-center justify-between">
        <p class="text-sm font-medium text-slate-500">{{ $label }}</p>
        @if ($icon)
            <div class="flex h-9 w-9 items-center justify-center rounded-lg {{ $accents[$accent] }}">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" />
                </svg>
            </div>
        @endif
    </div>
    <p class="mt-3 text-3xl font-bold tracking-tight text-slate-900">{{ $value }}</p>
    {{ $slot }}
</div>
