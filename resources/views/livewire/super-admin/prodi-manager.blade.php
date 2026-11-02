<div>
    <x-page-header title="Program Studi" subtitle="Kelola daftar program studi yang menyelenggarakan jalur RPL.">
        <x-slot:actions>
            <button wire:click="tambah" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Prodi
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4">
        <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari kode atau nama prodi..." class="w-full max-w-sm rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Kode</th>
                        <th class="px-6 py-3">Nama Prodi</th>
                        <th class="px-6 py-3">Jenjang</th>
                        <th class="px-6 py-3">Mahasiswa</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($prodiList as $prodi)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $prodi->kode }}</td>
                            <td class="px-6 py-3 text-slate-700">{{ $prodi->nama }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ $prodi->jenjang }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ $prodi->mahasiswa_count }}</td>
                            <td class="px-6 py-3">
                                <button wire:click="toggleAktif({{ $prodi->id }})">
                                    <x-badge :color="$prodi->is_aktif ? 'emerald' : 'slate'">
                                        {{ $prodi->is_aktif ? 'Aktif' : 'Non-aktif' }}
                                    </x-badge>
                                </button>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex justify-end gap-3">
                                    <button wire:click="edit({{ $prodi->id }})" class="font-medium text-indigo-600 hover:text-indigo-500">Ubah</button>
                                    <button wire:click="hapus({{ $prodi->id }})" wire:confirm="Yakin ingin menghapus program studi ini?" class="font-medium text-rose-600 hover:text-rose-500">Hapus</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">Belum ada program studi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginasi :paginator="$prodiList" />
    </div>

    <x-modal :show="$showModal" :title="$editingId ? 'Ubah Program Studi' : 'Tambah Program Studi'">
        <form wire:submit="simpan" class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Kode Prodi</label>
                <input wire:model="kode" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="mis. AKM">
                @error('kode') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Program Studi</label>
                <input wire:model="nama" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="mis. Akuntansi Manajerial">
                @error('nama') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Jenjang</label>
                <select wire:model="jenjang" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    <option value="D3">D3</option>
                    <option value="D4">D4</option>
                    <option value="S1">S1</option>
                </select>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_aktif" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                Program studi aktif
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="closeModal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
            </div>
        </form>
    </x-modal>
</div>
