<div>
    <x-page-header title="Dashboard Asesor" subtitle="Daftar mahasiswa yang ditugaskan kepada Anda." />

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    @if ($asesor)
        <div @class(['mb-6 rounded-2xl border p-5 shadow-sm', 'border-slate-200 bg-white' => $asesor->tanda_tangan, 'border-amber-200 bg-amber-50' => ! $asesor->tanda_tangan])>
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    @if ($asesor->tanda_tangan)
                        <img src="{{ route('asesor.ttd') }}?v={{ md5($asesor->tanda_tangan) }}" alt="Tanda tangan saya" class="h-14 max-w-[10rem] rounded-lg bg-white object-contain p-1 ring-1 ring-slate-200">
                    @endif
                    <div>
                        <p class="font-semibold text-slate-900">Tanda tangan saya</p>
                        <p class="text-sm text-slate-600">{{ $asesor->tanda_tangan ? 'Sudah tersimpan, dicetak pada formulir penilaian (Form 5).' : 'Belum ada. Unggah gambar tanda tangan agar tercetak pada formulir penilaian.' }}</p>
                    </div>
                </div>
                @unless ($ubahTtd || ! $asesor->tanda_tangan)
                    <button wire:click="$set('ubahTtd', true)" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ganti tanda tangan</button>
                @endunless
            </div>

            @if ($ubahTtd || ! $asesor->tanda_tangan)
                <form wire:submit="simpanTtd" class="mt-4 flex flex-wrap items-start gap-3">
                    <div class="min-w-0 flex-1">
                        <input wire:model="fileTtd" type="file" accept="image/png,image/jpeg" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                        <p class="mt-1 text-xs text-slate-500">PNG atau JPG, maks. 2 MB, latar putih/transparan.</p>
                        @error('fileTtd') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
                    </div>
                    @if ($fileTtd && method_exists($fileTtd, 'isPreviewable') && $fileTtd->isPreviewable() && ! $errors->has('fileTtd'))
                        <img src="{{ $fileTtd->temporaryUrl() }}" alt="Pratinjau" class="h-14 max-w-[10rem] rounded-lg bg-white object-contain p-1 ring-1 ring-slate-200">
                    @endif
                    <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">Simpan</button>
                </form>
            @endif
        </div>
    @endif

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Nama Mahasiswa</th>
                        <th class="px-6 py-3">Prodi</th>
                        <th class="px-6 py-3">Skema</th>
                        <th class="px-6 py-3">Progres Penilaian</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftar as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $item->mahasiswa->user->name }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $item->mahasiswa->prodi->nama }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $item->mahasiswa->skema->nama ?? '—' }}</td>
                            <td class="px-6 py-3">
                                <x-badge :color="$item->selesai === $item->total && $item->total > 0 ? 'emerald' : 'amber'">
                                    {{ $item->selesai }}/{{ $item->total }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('asesor.mahasiswa', $item->mahasiswa) }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Nilai →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada mahasiswa yang berkasnya siap dinilai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
