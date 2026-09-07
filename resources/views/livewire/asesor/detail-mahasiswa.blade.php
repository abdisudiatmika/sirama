<div>
    <x-page-header :title="$mahasiswa->user->name" :subtitle="$mahasiswa->prodi->nama.' · '.($mahasiswa->skema->nama ?? '—')">
        <x-slot:actions>
            <a href="{{ route('asesor.dashboard') }}" wire:navigate class="text-sm font-medium text-slate-500 hover:text-slate-700">← Kembali ke Daftar</a>
        </x-slot:actions>
    </x-page-header>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Matakuliah</th>
                        <th class="px-6 py-3">Jenis Bukti</th>
                        <th class="px-6 py-3">Status</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($pengajuanList as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3">
                                <p class="font-medium text-slate-900">{{ $item->pengajuan->matakuliah->kode }}</p>
                                <p class="text-xs text-slate-500">{{ $item->pengajuan->matakuliah->nama }}</p>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex gap-1.5">
                                    @foreach ($item->jenis as $j)
                                        <x-badge :color="$j === 'formal' ? 'indigo' : 'amber'">{{ ucfirst($j) }}</x-badge>
                                    @endforeach
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <x-badge :color="$item->selesai ? 'emerald' : 'slate'">{{ $item->selesai ? 'Selesai' : 'Belum Dinilai' }}</x-badge>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('asesor.penilaian', [$mahasiswa, $item->pengajuan]) }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Nilai →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400">Mahasiswa ini belum mengajukan matakuliah apa pun.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
