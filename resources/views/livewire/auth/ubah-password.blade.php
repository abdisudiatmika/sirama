<div>
    <div class="mx-auto max-w-lg">
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h2 class="text-base font-semibold text-slate-900">Ubah Password</h2>
            <p class="mt-1 text-sm text-slate-500">Gunakan password yang kuat dan tidak dipakai di layanan lain.</p>

            <form wire:submit="submit" class="mt-6 space-y-5">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Password Lama</label>
                    <input wire:model="password_lama" type="password" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                    @error('password_lama') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Password Baru</label>
                    <input wire:model="password_baru" type="password" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                    @error('password_baru') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Konfirmasi Password Baru</label>
                    <input wire:model="password_baru_confirmation" type="password" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm">
                </div>

                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Simpan Password Baru
                </button>
            </form>
        </div>
    </div>
</div>
