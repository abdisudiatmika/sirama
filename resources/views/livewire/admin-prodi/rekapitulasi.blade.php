<div>
    <x-page-header title="Formulir Rekapitulasi Hasil Asesmen" subtitle="Nilai final ditetapkan melalui rapat pleno program studi.">
        <x-slot:actions>
            <a href="{{ route('admin-prodi.monitoring') }}" wire:navigate class="text-sm font-medium text-slate-500 hover:text-slate-700">← Kembali</a>
            <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.32 0H6.34" /></svg>
                Cetak
            </button>
        </x-slot:actions>
    </x-page-header>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 print:border-0 print:shadow-none">
        <div class="border-b border-slate-200 pb-6">
            <h3 class="text-center text-base font-bold uppercase text-slate-900">Formulir Rekapitulasi Hasil Asesmen</h3>
            <p class="text-center text-sm text-slate-500">{{ $mahasiswa->prodi->nama }} — Jalur Rekognisi Pembelajaran Lampau</p>

            <div class="mt-6 grid grid-cols-1 gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                <div class="flex"><span class="w-40 flex-shrink-0 text-slate-500">Nama</span><span class="font-medium text-slate-900">: {{ $mahasiswa->biodata->nama_lengkap ?? $mahasiswa->user->name }}</span></div>
                <div class="flex"><span class="w-40 flex-shrink-0 text-slate-500">No HP</span><span class="font-medium text-slate-900">: {{ $mahasiswa->biodata->telp_rumah_hp ?? '—' }}</span></div>
                <div class="flex"><span class="w-40 flex-shrink-0 text-slate-500">Alamat</span><span class="font-medium text-slate-900">: {{ $mahasiswa->biodata->alamat_rumah ?? '—' }}</span></div>
                <div class="flex"><span class="w-40 flex-shrink-0 text-slate-500">Email</span><span class="font-medium text-slate-900">: {{ $mahasiswa->biodata->email ?? $mahasiswa->user->email }}</span></div>
                @php
                    $pendidikan = $mahasiswa->pendidikanTerakhir;
                    $jenjangLabel = '—';
                    if ($pendidikan) {
                        $institusi = filled($pendidikan->nama_pt) ? $pendidikan->nama_pt : $pendidikan->nama_sekolah;
                        $jenjangLabel = trim(($pendidikan->program ? $pendidikan->program.' ' : '').$institusi);
                    }
                @endphp
                <div class="flex sm:col-span-2"><span class="w-40 flex-shrink-0 text-slate-500">Jenjang Pendidikan Sebelumnya</span><span class="font-medium text-slate-900">: {{ $jenjangLabel }}</span></div>
            </div>
        </div>

        <div class="mt-6 overflow-x-auto">
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-3 py-3">No</th>
                        <th class="px-3 py-3">Kode MK</th>
                        <th class="px-3 py-3">Matakuliah</th>
                        <th class="px-3 py-3">Skor Mandiri</th>
                        @foreach ($asesorUrutan as $penugasan)
                            <th class="px-3 py-3">Asesor {{ $penugasan->urutan }}</th>
                        @endforeach
                        <th class="px-3 py-3">Rata-rata</th>
                        <th class="px-3 py-3">Skor Min.</th>
                        <th class="px-3 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($baris as $i => $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-3 text-slate-500">{{ $i + 1 }}</td>
                            <td class="px-3 py-3 font-mono text-slate-600">{{ $row->matakuliah->kode }}</td>
                            <td class="px-3 py-3 text-slate-800">
                                {{ $row->matakuliah->nama }}
                                @if ($row->matakuliah->dapat_direkognisi)
                                    <span class="text-indigo-500">*</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ $row->pengajuan?->nilai_mandiri_angka ?? '—' }}</td>
                            @foreach ($row->nilai_per_asesor as $nilai)
                                <td class="px-3 py-3 text-slate-600">{{ $nilai ?? '—' }}</td>
                            @endforeach
                            <td class="px-3 py-3">
                                @if ($row->rata_rata !== null)
                                    <span class="font-semibold text-slate-900">{{ $row->rata_rata }}</span>
                                    @if ($row->jumlah_penilai < $row->total_asesor)
                                        <span class="text-xs text-amber-600">({{ $row->jumlah_penilai }} dari {{ $row->total_asesor }} asesor)</span>
                                    @endif
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600">{{ $row->matakuliah->skor_minimum }}</td>
                            <td class="px-3 py-3">
                                <select
                                    wire:change="simpanStatus({{ $row->matakuliah->id }}, $event.target.value)"
                                    class="rounded-lg border-0 px-2.5 py-1.5 text-xs font-medium ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 print:ring-0"
                                >
                                    <option value="" @selected(!$row->status)>— Belum Diputuskan —</option>
                                    <option value="Direkognisi" @selected($row->status === 'Direkognisi')>Direkognisi</option>
                                    <option value="Tidak Direkognisi" @selected($row->status === 'Tidak Direkognisi')>Tidak Direkognisi</option>
                                </select>
                                @if (! $row->status && $row->usulan)
                                    <p class="mt-1 text-xs text-slate-400">Usulan sistem: {{ $row->usulan }}</p>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + $asesorUrutan->count() }}" class="px-3 py-8 text-center text-slate-400">Mahasiswa belum memiliki skema aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-6 text-xs text-slate-400">Matakuliah bertanda <span class="text-indigo-500">*</span> dapat direkognisi. Status ditetapkan melalui rapat pleno program studi dan dapat diubah kapan saja oleh admin prodi.</p>
    </div>
</div>
