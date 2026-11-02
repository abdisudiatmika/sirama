<div>
    <x-page-header title="Monitoring Penilaian" subtitle="Progres penilaian tiap asesor dan status berkas mahasiswa.">
        <x-slot:actions>
            <x-prodi-switcher :options="$prodiOptions" :selected="$prodiId" />
        </x-slot:actions>
    </x-page-header>

    <div class="mb-4 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nama / NIM..." class="rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">

        <select wire:model.live="filterSkemaId" class="rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            <option value="">Semua Skema</option>
            @foreach ($skemaOptions as $skema)
                <option value="{{ $skema->id }}">{{ $skema->nama }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterTahunMasuk" class="rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            <option value="">Semua Angkatan</option>
            @foreach ($tahunMasukOptions as $tahun)
                <option value="{{ $tahun }}">{{ $tahun }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterAsesorId" class="rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            <option value="">Semua Asesor</option>
            @foreach ($asesorOptions as $asesor)
                <option value="{{ $asesor->id }}">{{ $asesor->user->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="filterStatus" class="rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
            <option value="">Semua Status</option>
            <option value="draft">Draft</option>
            <option value="diajukan">Diajukan</option>
            <option value="sedang_dinilai">Sedang Dinilai</option>
            <option value="selesai_dinilai">Selesai Dinilai</option>
            <option value="dikembalikan">Dikembalikan</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">NIM</th>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Skema</th>
                        <th class="px-6 py-3">Progres per Asesor</th>
                        <th class="px-6 py-3">Status Berkas</th>
                        <th class="px-6 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @php
                        $statusLabel = [
                            'draft' => ['Draft', 'slate'],
                            'diajukan' => ['Diajukan', 'indigo'],
                            'sedang_dinilai' => ['Sedang Dinilai', 'amber'],
                            'selesai_dinilai' => ['Selesai Dinilai', 'emerald'],
                            'dikembalikan' => ['Dikembalikan', 'rose'],
                        ];
                    @endphp
                    @forelse ($mahasiswaList as $mhs)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-mono text-slate-600">{{ $mhs->nim }}</td>
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $mhs->user->name }}</td>
                            <td class="px-6 py-3 text-slate-600">{{ $mhs->skema->nama ?? '—' }}</td>
                            <td class="px-6 py-3">
                                <div class="flex flex-wrap gap-1.5">
                                    @forelse ($mhs->progresAsesor as $p)
                                        <x-badge :color="$p->total > 0 && $p->selesai === $p->total ? 'emerald' : 'slate'">
                                            Asesor {{ $p->urutan }}: {{ $p->selesai }}/{{ $p->total }}
                                        </x-badge>
                                    @empty
                                        <span class="text-xs text-slate-400">Belum ada asesor ditugaskan</span>
                                    @endforelse
                                </div>
                            </td>
                            <td class="px-6 py-3">
                                <x-badge :color="$statusLabel[$mhs->status_berkas][1]">{{ $statusLabel[$mhs->status_berkas][0] }}</x-badge>
                            </td>
                            <td class="px-6 py-3 text-right">
                                <a href="{{ route('admin-prodi.rekapitulasi', $mhs) }}" wire:navigate class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Lihat Rekap →</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-slate-400">Tidak ada mahasiswa yang cocok dengan filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginasi :paginator="$mahasiswaList" />
    </div>
</div>
