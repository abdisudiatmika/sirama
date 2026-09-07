@props(['show' => false, 'title' => '', 'maxWidth' => 'lg'])

@php
    $widths = [
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
    ];
@endphp

@if ($show)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="fixed inset-0 bg-slate-950/50 transition-opacity" wire:click="{{ $attributes->get('onclose', 'closeModal') }}"></div>

        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative w-full {{ $widths[$maxWidth] }} rounded-2xl bg-white p-6 shadow-xl">
                <div class="mb-5 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-slate-900">{{ $title }}</h3>
                    <button type="button" wire:click="{{ $attributes->get('onclose', 'closeModal') }}" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
@endif
