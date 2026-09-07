<div>
    <x-page-header title="Skema" subtitle="Paket matakuliah yang ditawarkan untuk direkognisi.">
        <x-slot:actions>
            <x-prodi-switcher :options="$prodiOptions" :selected="$prodiId" />
            <button wire:click="tambah" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Skema
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($skemaList as $skema)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between">
                    <div>
                        <x-badge :color="$skema->jenis === 'RPL' ? 'indigo' : 'slate'">{{ $skema->jenis }}</x-badge>
                        <h3 class="mt-2 text-sm font-semibold text-slate-900">{{ $skema->nama }}</h3>
                        <p class="text-xs text-slate-500">{{ $skema->tahun_akademik }}</p>
                    </div>
                    <button wire:click="toggleAktif({{ $skema->id }})">
                        <x-badge :color="$skema->is_aktif ? 'emerald' : 'slate'">{{ $skema->is_aktif ? 'Aktif' : 'Non-aktif' }}</x-badge>
                    </button>
                </div>

                <div class="mt-4 flex gap-4 text-sm text-slate-600">
                    <span>{{ $skema->matakuliah_count }} matakuliah</span>
                    <span>{{ $skema->mahasiswa_count }} mahasiswa</span>
                </div>

                <div class="mt-4 flex justify-end gap-3 border-t border-slate-100 pt-3">
                    <button wire:click="edit({{ $skema->id }})" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ubah</button>
                    <button wire:click="hapus({{ $skema->id }})" wire:confirm="Yakin ingin menghapus skema ini?" class="text-sm font-medium text-rose-600 hover:text-rose-500">Hapus</button>
                </div>
            </div>
        @empty
            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 py-12 text-center text-slate-400">
                Belum ada skema untuk prodi ini.
            </div>
        @endforelse
    </div>

    @if ($skemaList->hasPages())
        <div class="mt-4">
            {{ $skemaList->links() }}
        </div>
    @endif

    <x-modal :show="$showModal" :title="$editingId ? 'Ubah Skema' : 'Tambah Skema'" max-width="2xl">
        <form wire:submit="simpan" class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Skema</label>
                <input wire:model="nama" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="mis. Akuntansi Manajerial — Skema RPL">
                @error('nama') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Jenis</label>
                    <select wire:model="jenis" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        <option value="RPL">RPL</option>
                        <option value="Reguler">Reguler</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Tahun Akademik</label>
                    <input wire:model="tahun_akademik" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="2026/2027">
                    @error('tahun_akademik') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Matakuliah Anggota Skema</label>
                <div class="max-h-56 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-3">
                    @forelse ($matakuliahOptions as $mk)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="selectedMatakuliah" value="{{ $mk->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                            {{ $mk->kode }} — {{ $mk->nama }} <span class="text-slate-400">({{ $mk->sks }} SKS)</span>
                        </label>
                    @empty
                        <p class="text-sm text-slate-400">Belum ada matakuliah aktif. Tambahkan matakuliah terlebih dahulu.</p>
                    @endforelse
                </div>
            </div>

            <label class="flex items-center gap-2 text-sm text-slate-700">
                <input wire:model="is_aktif" type="checkbox" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                Skema aktif
            </label>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="closeModal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
            </div>
        </form>
    </x-modal>
</div>
