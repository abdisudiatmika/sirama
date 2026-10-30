@props(['show' => false, 'title' => '', 'subtitle' => null, 'onclose' => 'closePanel'])

@if ($show)
    <div
        class="fixed inset-0 z-50"
        x-data="{ open: false }"
        x-init="$nextTick(() => open = true)"
        @keydown.escape.window="$wire.{{ $onclose }}()"
    >
        <div
            x-show="open"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 bg-slate-950/40"
            wire:click="{{ $onclose }}"
        ></div>

        <div
            x-show="open"
            x-transition:enter="transform transition ease-out duration-200"
            x-transition:enter-start="translate-x-full"
            x-transition:enter-end="translate-x-0"
            class="fixed inset-y-0 right-0 flex w-full max-w-md flex-col bg-white shadow-2xl"
            role="dialog"
            aria-modal="true"
        >
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                <div class="min-w-0">
                    <h3 class="truncate text-base font-semibold text-slate-900">{{ $title }}</h3>
                    @if ($subtitle)
                        <p class="mt-0.5 text-sm text-slate-500">{{ $subtitle }}</p>
                    @endif
                </div>
                <button type="button" wire:click="{{ $onclose }}" class="rounded-lg p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                    <span class="sr-only">Tutup</span>
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-6 py-5">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="border-t border-slate-200 bg-slate-50 px-6 py-4">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
@endif
