<div>
    <x-page-header title="Dashboard Admin Prodi" subtitle="Ringkasan data program studi yang Anda kelola.">
        <x-slot:actions>
            <x-prodi-switcher :options="$prodiOptions" :selected="$prodiId" />
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <x-stat-card label="Matakuliah" :value="$stats['matakuliah']" accent="indigo" icon="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25" />
        <x-stat-card label="Skema" :value="$stats['skema']" accent="amber" icon="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6z" />
        <x-stat-card label="Mahasiswa RPL" :value="$stats['mahasiswa']" accent="emerald" icon="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" />
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-900">Status Kelengkapan Berkas Mahasiswa</h3>
        @php
            $labels = [
                'draft' => ['Draft', 'slate'],
                'diajukan' => ['Diajukan', 'indigo'],
                'sedang_dinilai' => ['Sedang Dinilai', 'amber'],
                'selesai_dinilai' => ['Selesai Dinilai', 'emerald'],
                'dikembalikan' => ['Dikembalikan', 'rose'],
            ];
        @endphp
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-5">
            @foreach ($labels as $key => [$label, $color])
                <div class="rounded-xl border border-slate-100 p-3 text-center">
                    <p class="text-2xl font-bold text-slate-900">{{ $statusBerkas[$key] ?? 0 }}</p>
                    <x-badge :color="$color" class="mt-1">{{ $label }}</x-badge>
                </div>
            @endforeach
        </div>
    </div>
</div>
