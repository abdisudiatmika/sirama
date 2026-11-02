@props(['paginator'])

@php $pilihan = config('rpl.pilihan_baris_per_halaman'); @endphp

{{-- Hidden only when even the smallest choice fits everything, so a larger choice can always be undone. --}}
@if ($paginator->total() > $pilihan[0])
    <div {{ $attributes->merge(['class' => 'flex flex-col gap-3 border-t border-slate-200 px-6 py-3 lg:flex-row lg:items-center lg:justify-between']) }}>
        <label class="flex items-center gap-2 text-sm text-slate-500">
            Tampilkan
            <select wire:model.live="perPage" class="rounded-lg border-0 py-1.5 pl-3 pr-8 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                @foreach ($pilihan as $jumlah)
                    <option value="{{ $jumlah }}" @selected($paginator->perPage() === $jumlah)>{{ $jumlah }}</option>
                @endforeach
            </select>
            baris per halaman
        </label>
        @if ($paginator->hasPages())
            <div class="min-w-0 flex-1 lg:max-w-3xl">{{ $paginator->links() }}</div>
        @endif
    </div>
@endif
