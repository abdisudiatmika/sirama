<div>
    <x-page-header title="Dashboard Mahasiswa" subtitle="Status pengajuan berkas RPL Anda." />

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
