<div>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-white">Masuk ke akun Anda</h2>
        <p class="mt-2 text-sm text-slate-400">Sistem Informasi Penerimaan Mahasiswa RPL</p>
    </div>

    @if (session('error'))
        <div class="mb-5 flex items-start gap-2 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3.5 py-2.5 text-sm text-rose-300">
            <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <form wire:submit="submit" class="space-y-5">
        <div>
            <label for="login" class="mb-1.5 block text-sm font-medium text-slate-300">Username atau Email</label>
            <input
                wire:model="login"
                id="login"
                type="text"
                autofocus
                autocomplete="username"
                class="block w-full rounded-lg border-0 bg-white/5 px-3.5 py-2.5 text-white ring-1 ring-inset ring-white/10 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm"
                placeholder="nama.pengguna"
            >
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-slate-300">Password</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="current-password"
                class="block w-full rounded-lg border-0 bg-white/5 px-3.5 py-2.5 text-white ring-1 ring-inset ring-white/10 placeholder:text-slate-500 focus:ring-2 focus:ring-inset focus:ring-indigo-500 sm:text-sm"
                placeholder="••••••••"
            >
        </div>

        @error('login')
            <div class="flex items-start gap-2 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3.5 py-2.5 text-sm text-rose-300">
                <svg class="mt-0.5 h-4 w-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
                <span>{{ $message }}</span>
            </div>
        @enderror

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="submit"
            class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-3.5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-500 disabled:opacity-60"
        >
            <svg wire:loading wire:target="submit" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            Masuk
        </button>
    </form>

    <p class="mt-8 text-center text-xs text-slate-500">
        Lupa password? Hubungi admin prodi atau super admin untuk reset akun.
    </p>
</div>
