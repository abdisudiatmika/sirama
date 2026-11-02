<div>
    <x-page-header title="Admin Prodi" subtitle="Kelola akun admin prodi dan pemetaannya ke satu atau lebih program studi.">
        <x-slot:actions>
            <button wire:click="tambah" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Admin Prodi
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
        <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nama atau username..." class="w-full max-w-sm rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Username</th>
                        <th class="px-6 py-3">Prodi Dikelola</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($admins as $admin)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <p class="font-medium text-slate-900">{{ $admin->name }}</p>
                                <p class="text-xs text-slate-500">{{ $admin->email }}</p>
                            </td>
                            <td class="px-6 py-3 font-mono text-slate-600">{{ $admin->username }}</td>
                            <td class="px-6 py-3">
                                <div class="flex flex-wrap gap-1">
                                    @forelse ($admin->prodiDikelola as $p)
                                        <x-badge color="indigo">{{ $p->kode }}</x-badge>
                                    @empty
                                        <span class="text-xs text-slate-400">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <button wire:click="toggleLock({{ $admin->id }})">
                                    <x-badge :color="$admin->is_locked ? 'rose' : 'emerald'">
                                        {{ $admin->is_locked ? 'Terkunci' : 'Aktif' }}
                                    </x-badge>
                                </button>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex justify-end gap-3">
                                    <button wire:click="resetPassword({{ $admin->id }})" wire:confirm="Buat password baru untuk akun ini?" class="font-medium text-slate-600 hover:text-slate-800">Reset Password</button>
                                    <button wire:click="edit({{ $admin->id }})" class="font-medium text-indigo-600 hover:text-indigo-500">Ubah</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada akun admin prodi.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginasi :paginator="$admins" />
    </div>

    <x-modal :show="$showModal" :title="$editingId ? 'Ubah Admin Prodi' : 'Tambah Admin Prodi'" max-width="xl">
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
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Program Studi Dikelola</label>
                <div class="grid grid-cols-2 gap-2 rounded-lg border border-slate-200 p-3">
                    @foreach ($prodiOptions as $p)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="selectedProdi" value="{{ $p->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                            {{ $p->kode }} — {{ $p->nama }}
                        </label>
                    @endforeach
                </div>
                @error('selectedProdi') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            @unless ($editingId)
                <p class="text-xs text-slate-500">Password awal akan dibuat otomatis dan ditampilkan setelah akun disimpan.</p>
            @endunless

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="closeModal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
            </div>
        </form>
    </x-modal>
</div>
