<div>
    <x-page-header title="Dashboard Asesor" subtitle="Daftar mahasiswa yang ditugaskan kepada Anda." />

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Nama Mahasiswa</th>
                        <th class="px-6 py-3">Prodi</th>
                        <th class="px-6 py-3">Skema</th>
                        <th class="px-6 py-3">Progres Penilaian</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftar as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $item->mahasiswa->user->name }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $item->mahasiswa->prodi->nama }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $item->mahasiswa->skema->nama ?? '—' }}</td>
                            <td class="px-6 py-3">
                                <x-badge :color="$item->selesai === $item->total && $item->total > 0 ? 'emerald' : 'amber'">
                                    {{ $item->selesai }}/{{ $item->total }}
                                </x-badge>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('asesor.mahasiswa', $item->mahasiswa) }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Nilai →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada mahasiswa yang berkasnya siap dinilai.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
