<div>
    <x-page-header title="Input Nilai" subtitle="Masukkan nilai asesor untuk banyak mahasiswa sekaligus lewat satu file Excel.">
        <x-slot:actions>
            <x-prodi-switcher :options="$prodiOptions" :selected="$prodiId" />
        </x-slot:actions>
    </x-page-header>

    @if ($pesan)
        <div class="mb-4 flex items-start justify-between gap-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <p>{{ $pesan }}</p>
            <button wire:click="$set('pesan', null)" class="text-emerald-500 hover:text-emerald-700">&times;</button>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4">
        {{-- Langkah 1 --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center gap-3">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">1</span>
                <h2 class="font-semibold text-slate-900">Unduh template</h2>
            </div>
            <p class="mb-4 text-sm text-slate-500">Pilih mahasiswa yang akan dinilai. Template berisi satu baris per mahasiswa per mata kuliah yang diajukan, sudah terisi nilai yang ada.</p>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <select wire:model.live="filterSkemaId" class="rounded-lg border-0 px-3 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    <option value="">Semua Skema</option>
                    @foreach ($skemaOptions as $skema)
                        <option value="{{ $skema->id }}">{{ $skema->nama }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterAngkatan" class="rounded-lg border-0 px-3 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    <option value="">Semua Angkatan</option>
                    @foreach ($angkatanOptions as $tahun)
                        <option value="{{ $tahun }}">{{ $tahun }}</option>
                    @endforeach
                </select>
                <select wire:model.live="filterStatus" class="rounded-lg border-0 px-3 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    <option value="">Semua Status</option>
                    <option value="draft">Draft</option>
                    <option value="diajukan">Diajukan</option>
                    <option value="sedang_dinilai">Sedang Dinilai</option>
                    <option value="selesai_dinilai">Selesai Dinilai</option>
                    <option value="dikembalikan">Dikembalikan</option>
                </select>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-sm text-slate-600"><span class="font-semibold text-slate-900">{{ $jumlahMahasiswa }}</span> mahasiswa · <span class="font-semibold text-slate-900">{{ number_format($jumlahBaris, 0, ',', '.') }}</span> baris mata kuliah</p>
                <a href="{{ $urlTemplate }}" @class(['inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm', 'bg-indigo-600 text-white hover:bg-indigo-500' => $jumlahMahasiswa, 'pointer-events-none bg-slate-200 text-slate-400' => ! $jumlahMahasiswa])>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Unduh Template Excel
                </a>
            </div>
        </div>

        {{-- Langkah 2 --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-3 flex items-center gap-3">
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">2</span>
                <h2 class="font-semibold text-slate-900">Unggah dan periksa</h2>
            </div>
            <p class="mb-4 text-sm text-slate-500">Isi kolom Asesor 1–3 dengan nilai 0–100. Sel kosong tidak mengubah nilai yang sudah ada. Sistem memeriksa file dulu; nilai baru tersimpan setelah Anda klik Simpan.</p>

            <form wire:submit="periksa" class="flex flex-wrap items-center gap-3">
                <input type="file" wire:model="file" accept=".xlsx" class="block w-full min-w-0 flex-1 text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
                <button type="submit" wire:loading.attr="disabled" wire:target="file,periksa" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 disabled:opacity-50">
                    <span wire:loading.remove wire:target="periksa">Periksa File</span>
                    <span wire:loading wire:target="periksa">Memeriksa…</span>
                </button>
            </form>
            @error('file') <p class="mt-2 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    @if ($pratinjau)
        @php $r = $pratinjau['ringkasan']; @endphp
        <div class="mt-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-sm font-semibold text-white">3</span>
                    <h2 class="font-semibold text-slate-900">Pratinjau</h2>
                </div>
                <div class="flex gap-2">
                    <button wire:click="batal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                    <button wire:click="simpan" wire:loading.attr="disabled" wire:target="simpan" @disabled($pratinjau['galat'] || ! $r['berubah'])
                        wire:confirm="Simpan {{ $r['berubah'] }} nilai untuk {{ count($r['mahasiswa']) }} mahasiswa?"
                        class="rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 disabled:cursor-not-allowed disabled:opacity-40">
                        <span wire:loading.remove wire:target="simpan">Simpan Nilai</span>
                        <span wire:loading wire:target="simpan">Menyimpan…</span>
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                <div class="rounded-lg bg-slate-50 px-4 py-3"><p class="text-xs text-slate-500">Nilai terbaca</p><p class="text-xl font-semibold text-slate-900">{{ number_format($r['nilai'], 0, ',', '.') }}</p></div>
                <div class="rounded-lg bg-emerald-50 px-4 py-3"><p class="text-xs text-emerald-700">Nilai baru / berubah</p><p class="text-xl font-semibold text-emerald-800">{{ number_format($r['berubah'], 0, ',', '.') }}</p></div>
                <div class="rounded-lg {{ $pratinjau['galat'] ? 'bg-rose-50' : 'bg-slate-50' }} px-4 py-3"><p class="text-xs {{ $pratinjau['galat'] ? 'text-rose-700' : 'text-slate-500' }}">Masalah</p><p class="text-xl font-semibold {{ $pratinjau['galat'] ? 'text-rose-700' : 'text-slate-900' }}">{{ count($pratinjau['galat']) }}</p></div>
            </div>

            @if ($pratinjau['galat'])
                <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                    <p class="font-semibold">Perbaiki masalah berikut di file, lalu unggah dan periksa lagi. Belum ada nilai yang disimpan.</p>
                    <ul class="mt-2 list-disc space-y-0.5 pl-5">
                        @foreach (array_slice($pratinjau['galat'], 0, 20) as $g)
                            <li>{{ $g }}</li>
                        @endforeach
                    </ul>
                    @if (count($pratinjau['galat']) > 20)
                        <p class="mt-1">… dan {{ count($pratinjau['galat']) - 20 }} masalah lain.</p>
                    @endif
                </div>
            @elseif (! $r['berubah'])
                <p class="mt-4 text-sm text-slate-500">Tidak ada nilai yang berbeda dari yang sudah tersimpan.</p>
            @else
                <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200">
                    <table class="min-w-full divide-y divide-slate-200 text-sm">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">NIM</th>
                                <th class="px-4 py-2.5 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Nama</th>
                                <th class="px-4 py-2.5 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Nilai baru / berubah</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($r['mahasiswa'] as $m)
                                <tr>
                                    <td class="px-4 py-2 font-mono text-slate-600">{{ $m['nim'] }}</td>
                                    <td class="px-4 py-2 text-slate-900">{{ $m['nama'] }}</td>
                                    <td class="px-4 py-2 text-right font-semibold text-emerald-700">{{ $m['berubah'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @endif
</div>
