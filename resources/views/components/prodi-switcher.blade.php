@props(['options', 'selected'])

@if ($options->count() > 1)
    <select wire:model.live="prodiId" class="rounded-lg border-0 px-3.5 py-2.5 text-sm font-medium text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
        @foreach ($options as $option)
            <option value="{{ $option->id }}">{{ $option->kode }} — {{ $option->nama }}</option>
        @endforeach
    </select>
@else
    <x-badge color="indigo">{{ $options->first()?->nama }}</x-badge>
@endif
