<div>
    <x-page-header title="Dashboard Mahasiswa" subtitle="Status pengajuan berkas RPL Anda." />

    @if ($mahasiswa?->status_berkas === 'dikembalikan' && $pengembalianTerakhir)
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 text-sm text-amber-900">
            <div class="flex items-center gap-2 font-semibold">
                <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                Berkas Anda dikembalikan untuk diperbaiki
            </div>
            <p class="mt-1.5">{{ $pengembalianTerakhir->alasan }}</p>
            <p class="mt-1.5 text-xs text-amber-700">Oleh {{ $pengembalianTerakhir->admin->name }} · {{ $pengembalianTerakhir->created_at->translatedFormat('d M Y, H:i') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Skema yang Diikuti</h3>
            @if ($mahasiswa?->skema)
                <p class="mt-2 text-lg font-semibold text-indigo-600">{{ $mahasiswa->skema->nama }}</p>
                <p class="text-sm text-slate-500">{{ $mahasiswa->skema->tahun_akademik }}</p>
            @else
                <p class="mt-2 text-sm text-slate-400">Belum ditetapkan oleh admin prodi.</p>
            @endif

            <div class="mt-6 flex items-center justify-between rounded-xl border border-indigo-100 bg-indigo-50 p-6">
                <div>
                    <p class="text-sm font-semibold text-indigo-900">
                        @if ($mahasiswa && $mahasiswa->status_berkas !== 'draft')
                            Status berkas: {{ str_replace('_', ' ', ucfirst($mahasiswa->status_berkas)) }}
                        @else
                            Lengkapi Form 1–6 untuk mengajukan berkas RPL Anda.
                        @endif
                    </p>
                </div>
                <a href="{{ route('mahasiswa.wizard') }}" wire:navigate class="flex-shrink-0 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    Buka Pengajuan RPL
                </a>
            </div>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-900">Asesor Anda</h3>
            <ul class="mt-3 space-y-2">
                @forelse ($mahasiswa?->penugasanAsesor ?? [] as $penugasan)
                    <li class="flex items-center justify-between text-sm">
                        <span class="text-slate-700">Asesor {{ $penugasan->urutan }}</span>
                        <span class="font-medium text-slate-900">{{ $penugasan->asesor->user->name }}</span>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">Belum ada asesor ditugaskan.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
