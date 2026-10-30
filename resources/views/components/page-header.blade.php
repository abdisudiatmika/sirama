@props(['title', 'subtitle' => null])

<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-xl font-semibold text-slate-900">{{ $title }}</h2>
        @if ($subtitle)
            <p class="mt-1 text-sm text-slate-500">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex flex-shrink-0 flex-wrap items-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
