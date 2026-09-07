<div>
    <x-page-header title="Matakuliah & CPMK" subtitle="Kelola daftar matakuliah yang dapat direkognisi beserta CPMK-nya.">
        <x-slot:actions>
            <x-prodi-switcher :options="$prodiOptions" :selected="$prodiId" />
            <button wire:click="tambah" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Matakuliah
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4">
        <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari kode atau nama matakuliah..." class="w-full max-w-sm rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Kode</th>
                        <th class="px-6 py-3">Nama Matakuliah</th>
                        <th class="px-6 py-3">SKS</th>
                        <th class="px-6 py-3">Skor Minimum</th>
                        <th class="px-6 py-3">CPMK</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($matakuliahList as $mk)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $mk->kode }}</td>
                            <td class="px-6 py-3 text-slate-700">
                                {{ $mk->nama }}
                                @if ($mk->dapat_direkognisi)
                                    <span class="text-indigo-500" title="Dapat direkognisi">*</span>
                                @endif
                            </td>
                            <td class="px-6 py-3 text-slate-500">{{ $mk->sks }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ $mk->skor_minimum }}</td>
                            <td class="px-6 py-3">
                                <button wire:click="kelolaCpmk({{ $mk->id }})" class="font-medium text-indigo-600 hover:text-indigo-500">{{ $mk->cpmk_count }} CPMK</button>
                            </td>
                            <td class="px-6 py-3">
                                <button wire:click="toggleAktif({{ $mk->id }})">
                                    <x-badge :color="$mk->is_aktif ? 'emerald' : 'slate'">{{ $mk->is_aktif ? 'Aktif' : 'Non-aktif' }}</x-badge>
                                </button>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex justify-end gap-3">
                                    <button wire:click="edit({{ $mk->id }})" class="font-medium text-indigo-600 hover:text-indigo-500">Ubah</button>
                                    <button wire:click="hapus({{ $mk->id }})" wire:confirm="Yakin ingin menghapus matakuliah ini?" class="font-medium text-rose-600 hover:text-rose-500">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">Belum ada matakuliah untuk prodi ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($matakuliahList->hasPages())
            <div class="border-t border-slate-200 px-6 py-3">
                {{ $matakuliahList->links() }}
            </div>
        @endif
    </div>

    <x-modal :show="$showModal" :title="$editingId ? 'Ubah Matakuliah' : 'Tambah Matakuliah'" max-width="xl">
        <form wire:submit="simpan" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Kode Matakuliah</label>
                    <input wire:model="kode" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="mis. MKB56404">
                    @error('kode') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Semester</label>
                    <input wire:model="semester" type="number" min="1" max="14" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Matakuliah (sesuai CP Prodi)</label>
                <input wire:model="nama" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                @error('nama') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">SKS</label>
                    <input wire:model="sks" type="number" min="1" max="24" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('sks') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Skor Minimum Rekognisi</label>
                    <input wire:model="skor_minimum" type="number" min="0" max="100" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('skor_minimum') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="dapat_direkognisi" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                Dapat direkognisi (ditandai * pada rekap)
            </label>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_aktif" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                Matakuliah aktif
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="closeModal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showCpmkModal" title="Kelola CPMK" max-width="2xl" onclose="closeCpmkModal">
        <div class="space-y-4">
            <form wire:submit="tambahCpmk" class="flex items-end gap-3 rounded-xl border border-slate-100 bg-slate-50 p-4">
                <div class="w-28">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Kode</label>
                    <input wire:model="cpmkKode" type="text" class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="CPMK1">
                </div>
                <div class="flex-1">
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">Deskripsi</label>
                    <input wire:model="cpmkDeskripsi" type="text" class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="Mampu menjelaskan...">
                </div>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    {{ $editingCpmkId ? 'Simpan' : 'Tambah' }}
                </button>
                @if ($editingCpmkId)
                    <button type="button" wire:click="batalEditCpmk" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100">Batal</button>
                @endif
            </form>
            @error('cpmkKode') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
            @error('cpmkDeskripsi') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror

            <div class="max-h-80 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-100">
                @forelse ($cpmkList as $cpmk)
                    <div class="flex items-start justify-between gap-3 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $cpmk->kode }}</p>
                            <p class="text-sm text-slate-600">{{ $cpmk->deskripsi }}</p>
                        </div>
                        <div class="flex flex-shrink-0 gap-3">
                            <button wire:click="editCpmk({{ $cpmk->id }})" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ubah</button>
                            <button wire:click="hapusCpmk({{ $cpmk->id }})" wire:confirm="Hapus CPMK ini?" class="text-sm font-medium text-rose-600 hover:text-rose-500">Hapus</button>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-slate-400">Belum ada CPMK untuk matakuliah ini.</p>
                @endforelse
            </div>
        </div>
    </x-modal>
</div>
