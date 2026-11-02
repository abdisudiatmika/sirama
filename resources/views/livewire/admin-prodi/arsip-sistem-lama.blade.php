@php use Illuminate\Support\Number; @endphp
<div>
    <x-page-header title="Arsip Sistem Lama" subtitle="File yang diunggah mahasiswa di sistem SIRAMA lama. Hanya untuk dilihat dan diunduh.">
        <x-slot:actions>
            <x-prodi-switcher :options="$prodiOptions" :selected="$prodiId" />
        </x-slot:actions>
    </x-page-header>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Mahasiswa dengan arsip" :value="$ringkasan->mahasiswa ?? 0" accent="indigo" />
        <x-stat-card label="Jumlah file" :value="$ringkasan->file ?? 0" accent="emerald" />
        <x-stat-card label="Total ukuran" :value="Number::fileSize((int) ($ringkasan->ukuran ?? 0), precision: 1)" accent="amber" />
    </div>

    <div class="mb-4">
        <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nama atau NIM..." class="w-full max-w-sm rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">NIM</th>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">File</th>
                        <th class="px-6 py-3">Ukuran</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftar as $mhs)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-mono text-slate-600">{{ $mhs->nim }}</td>
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $mhs->user->name }}</td>
                            <td class="px-6 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @foreach ($mhs->lampiranLama->groupBy(fn ($l) => $l->namaLabel()) as $label => $item)
                                        <x-badge color="slate">{{ $label }} · {{ $item->count() }}</x-badge>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-3 whitespace-nowrap text-slate-600">{{ Number::fileSize((int) $mhs->lampiranLama->sum('ukuran'), precision: 1) }}</td>
                            <td class="px-6 py-3">
                                <div class="flex justify-end gap-3 whitespace-nowrap">
                                    <button wire:click="toggle({{ $mhs->id }})" class="font-medium text-slate-600 hover:text-slate-800">{{ $dibuka === $mhs->id ? 'Tutup' : 'Lihat file' }}</button>
                                    <a href="{{ route('admin-prodi.arsip-lama.zip', $mhs) }}" class="font-medium text-indigo-600 hover:text-indigo-500">Unduh .zip</a>
                                </div>
                            </td>
                        </tr>
                        @if ($dibuka === $mhs->id)
                            <tr class="bg-slate-50/60">
                                <td colspan="5" class="px-6 py-4">
                                    <ul class="divide-y divide-slate-200 rounded-xl border border-slate-200 bg-white">
                                        @foreach ($mhs->lampiranLama as $lampiran)
                                            @php $ada = $service->pathAbsolut($lampiran) !== null; @endphp
                                            <li class="flex items-center gap-3 px-4 py-2.5">
                                                <x-badge color="indigo">{{ $lampiran->namaLabel() }}</x-badge>
                                                <span class="min-w-0 flex-1 truncate {{ $ada ? 'text-slate-800' : 'text-slate-400 line-through' }}" title="{{ $lampiran->nama_file }}">{{ $lampiran->nama_file }}</span>
                                                <span class="whitespace-nowrap text-xs text-slate-400">{{ Number::fileSize((int) $lampiran->ukuran, precision: 1) }}</span>
                                                @if ($ada)
                                                    <a href="{{ route('admin-prodi.arsip-lama.file', $lampiran) }}" target="_blank" rel="noopener" class="whitespace-nowrap text-xs font-semibold text-indigo-600 hover:text-indigo-500">Buka</a>
                                                @else
                                                    <span class="whitespace-nowrap text-xs text-rose-500">File tidak ada di server</span>
                                                @endif
                                            </li>
                                        @endforeach
                                    </ul>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                @if ($search)
                                    Tidak ada mahasiswa yang cocok.
                                @else
                                    Belum ada arsip sistem lama untuk prodi ini.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginasi :paginator="$daftar" />
    </div>
</div>
