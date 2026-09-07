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

            <div class="mt-6 rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
                Wizard pengisian Form 1–6 akan tersedia pada fase pengembangan berikutnya.
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
