<div>
    <x-page-header title="Dashboard Asesor" subtitle="Daftar mahasiswa yang ditugaskan kepada Anda." />

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Nama Mahasiswa</th>
                        <th class="px-6 py-3">Prodi</th>
                        <th class="px-6 py-3">Progres Penilaian</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($asesor?->penugasan ?? [] as $penugasan)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $penugasan->mahasiswa->user->name }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $penugasan->mahasiswa->prodi->nama }}</td>
                            <td class="px-6 py-3 text-slate-500">Modul asesmen belum aktif</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-8 text-center text-slate-400">Belum ada mahasiswa yang ditugaskan kepada Anda.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
        Modul penilaian VATM per CPMK akan tersedia pada fase pengembangan berikutnya.
    </div>
</div>
