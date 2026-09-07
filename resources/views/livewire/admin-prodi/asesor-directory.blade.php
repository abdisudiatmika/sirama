<div>
    <x-page-header title="Direktori Asesor" subtitle="Akun asesor bersifat global. Cari dahulu sebelum membuat akun baru untuk menghindari duplikasi.">
        <x-slot:actions>
            <x-prodi-switcher :options="$prodiOptions" :selected="$prodiId" />
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
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($asesorList as $asesor)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <p class="font-medium text-slate-900">{{ $asesor->user->name }}</p>
                                <p class="text-xs text-slate-500">{{ $asesor->user->username }}</p>
                            </td>
                            <td class="px-6 py-3 font-mono text-slate-600">{{ $asesor->nidn ?? '—' }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $asesor->bidang_keahlian ?? '—' }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $asesor->penugasan_count }} mahasiswa</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                                Tidak ditemukan. Belum ada asesor yang cocok — silakan tambahkan akun baru.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($asesorList->hasPages())
            <div class="border-t border-slate-200 px-6 py-3">
                {{ $asesorList->links() }}
            </div>
        @endif
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
</div>
