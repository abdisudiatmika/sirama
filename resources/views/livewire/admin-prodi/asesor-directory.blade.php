<div>
    <x-page-header title="Direktori Asesor" subtitle="Akun asesor bersifat global. Cari dahulu sebelum membuat akun baru untuk menghindari duplikasi.">
        <x-slot:actions>
            <x-prodi-switcher :options="$prodiOptions" :selected="$prodiId" />
            <a href="{{ route('admin-prodi.asesor.ekspor') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Ekspor Excel (dengan TTD)
            </a>
            <a href="{{ route('admin-prodi.asesor.ttd-semua') }}" class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                Unduh Semua TTD (ZIP)
            </a>
            <button wire:click="tambah" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Asesor Baru
            </button>
        </x-slot:actions>
    </x-page-header>

    @if ($generatedPassword)
        <div class="mb-6 flex items-start justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 text-sm text-amber-900">
            <div>
                <p class="font-medium">Password awal untuk <span class="font-mono">{{ $generatedFor }}</span>:</p>
                <p class="mt-1 select-all rounded-lg bg-white px-3 py-1.5 font-mono text-base font-semibold tracking-wide text-amber-800 ring-1 ring-amber-200">{{ $generatedPassword }}</p>
                <p class="mt-1.5 text-xs text-amber-700">Catat & sampaikan ke pemilik akun sekarang — password ini tidak akan ditampilkan lagi.</p>
            </div>
            <button wire:click="tutupInfoPassword" class="text-amber-500 hover:text-amber-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('success') }}</div>
    @endif

    <div class="mb-4">
        <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nama, username, atau NIDN sebelum menambah baru..." class="w-full max-w-md rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">NIDN</th>
                        <th class="px-6 py-3">Bidang Keahlian</th>
                        <th class="px-6 py-3">Penugasan Aktif</th>
                        <th class="px-6 py-3">Tanda Tangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($asesorList as $asesor)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <p class="font-medium text-slate-900">{{ $asesor->user->name }}</p>
                            </td>
                            <td class="px-6 py-3 font-mono text-slate-600">{{ $asesor->nidn ?? '—' }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $asesor->bidang_keahlian ?? '—' }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $asesor->penugasan_count }} mahasiswa</td>
                            <td class="px-6 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($asesor->tanda_tangan)
                                        <img src="{{ route('admin-prodi.asesor.ttd', $asesor) }}?v={{ md5($asesor->tanda_tangan) }}" alt="TTD {{ $asesor->user->name }}" class="h-10 max-w-[7rem] rounded bg-white object-contain ring-1 ring-slate-200">
                                    @else
                                        <x-badge color="rose">Belum ada</x-badge>
                                    @endif
                                    <button wire:click="bukaTtd({{ $asesor->id }})" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">{{ $asesor->tanda_tangan ? 'Ganti' : 'Unggah' }}</button>
                                    @if ($asesor->tanda_tangan)
                                        <a href="{{ route('admin-prodi.asesor.ttd-unduh', $asesor) }}" class="text-sm font-medium text-slate-600 hover:text-slate-800">Unduh</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                Tidak ditemukan. Belum ada asesor yang cocok — silakan tambahkan akun baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginasi :paginator="$asesorList" />
    </div>

    <x-modal :show="$showModal" title="Tambah Asesor Baru" max-width="xl">
        <form wire:submit="simpan" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Lengkap</label>
                    <input wire:model="name" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Username</label>
                    <input wire:model="username" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('username') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                <input wire:model="email" type="email" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                <p class="mt-1 text-xs text-slate-500">Bila email/NIDN sudah terdaftar, sistem akan menolak untuk mencegah duplikasi — gunakan pencarian di atas.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">NIDN</label>
                    <input wire:model="nidn" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('nidn') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">No. HP</label>
                    <input wire:model="no_hp" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Bidang Keahlian</label>
                <input wire:model="bidang_keahlian" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="closeModal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$ttdAsesorId !== null" title="Tanda Tangan Asesor" max-width="lg">
        @if ($ttdAsesor)
            <form wire:submit="simpanTtd" class="space-y-4">
                <p class="text-sm text-slate-600">{{ $ttdAsesor->user->name }}</p>

                @if ($fileTtd && method_exists($fileTtd, 'isPreviewable') && $fileTtd->isPreviewable() && ! $errors->has('fileTtd'))
                    <div>
                        <p class="mb-1.5 text-xs font-medium text-slate-500">Pratinjau file baru</p>
                        <img src="{{ $fileTtd->temporaryUrl() }}" alt="Pratinjau" class="h-24 max-w-full rounded-lg bg-white object-contain p-2 ring-1 ring-slate-200">
                    </div>
                @elseif ($ttdAsesor->tanda_tangan)
                    <div>
                        <p class="mb-1.5 text-xs font-medium text-slate-500">Tanda tangan saat ini</p>
                        <img src="{{ route('admin-prodi.asesor.ttd', $ttdAsesor) }}?v={{ md5($ttdAsesor->tanda_tangan) }}" alt="TTD" class="h-24 max-w-full rounded-lg bg-white object-contain p-2 ring-1 ring-slate-200">
                    </div>
                @endif

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">File gambar tanda tangan</label>
                    <input wire:model="fileTtd" type="file" accept="image/png,image/jpeg" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="mt-1 text-xs text-slate-500">PNG atau JPG, maks. 2 MB. Sebaiknya latar putih/transparan dan sudah dipotong rapat.</p>
                    @error('fileTtd') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                    <div wire:loading wire:target="fileTtd" class="mt-1.5 text-xs text-slate-500">Mengunggah…</div>
                </div>

                <div class="flex items-center justify-between gap-3 pt-2">
                    <div>
                        @if ($ttdAsesor->tanda_tangan)
                            <a href="{{ route('admin-prodi.asesor.ttd-unduh', $ttdAsesor) }}" class="mr-4 text-sm font-medium text-slate-600 hover:text-slate-800">Unduh</a>
                            <button type="button" wire:click="hapusTtd" wire:confirm="Hapus tanda tangan asesor ini?" class="text-sm font-medium text-rose-600 hover:text-rose-700">Hapus TTD</button>
                        @endif
                    </div>
                    <div class="flex gap-3">
                        <button type="button" wire:click="tutupTtd" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                        <button type="submit" wire:loading.attr="disabled" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50">Simpan</button>
                    </div>
                </div>
            </form>
        @endif
    </x-modal>
</div>
