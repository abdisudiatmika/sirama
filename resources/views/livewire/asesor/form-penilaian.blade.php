<div>
    <x-page-header :title="$pengajuan->matakuliah->kode.' — '.$pengajuan->matakuliah->nama" subtitle="Penilaian untuk {{ $mahasiswa->user->name ?? '' }}">
        <x-slot:actions>
            <a href="{{ route('asesor.mahasiswa', $mahasiswa) }}" wire:navigate class="text-sm font-medium text-slate-500 hover:text-slate-700">← Kembali</a>
        </x-slot:actions>
    </x-page-header>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <!-- VATM per CPMK -->
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">
                    <h3 class="text-sm font-semibold text-slate-900">Validasi CPMK (VATM)</h3>
                    <button wire:click="centangSemuaVatmGlobal" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">Centang Semua CPMK</button>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach ($pengajuan->matakuliah->cpmk as $cpmk)
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900">{{ $cpmk->kode }}</p>
                                    <p class="text-sm text-slate-600">{{ $cpmk->deskripsi }}</p>
                                </div>
                                <button wire:click="centangSemuaVatm({{ $cpmk->id }})" class="flex-shrink-0 text-xs font-medium text-indigo-600 hover:text-indigo-500">Centang Semua</button>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-4">
                                @foreach (['valid' => 'Valid', 'asli' => 'Asli', 'terkini' => 'Terkini', 'memadai' => 'Memadai'] as $key => $label)
                                    <label class="flex items-center gap-2 text-sm text-slate-700">
                                        <input
                                            type="checkbox"
                                            @checked($vatm[$cpmk->id][$key] ?? false)
                                            wire:click="toggleVatm({{ $cpmk->id }}, '{{ $key }}')"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-600"
                                        >
                                        {{ $label }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Scoring forms -->
            @if (in_array('formal', $jenisTersedia))
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Penilaian Formal</h3>
                    <form wire:submit="simpanFormal" class="mt-4 space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Nilai (0–100)</label>
                            <input wire:model="nilaiFormal" type="number" min="0" max="100" class="block w-32 rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            @error('nilaiFormal') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Catatan Reviewer</label>
                            <textarea wire:model="catatanFormal" rows="2" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600"></textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Catatan Kesenjangan</label>
                            <textarea wire:model="kesenjanganFormal" rows="2" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600"></textarea>
                        </div>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan Penilaian Formal</button>
                    </form>
                </div>
            @endif

            @if (in_array('informal', $jenisTersedia))
                <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Penilaian Informal</h3>
                    <form wire:submit="simpanInformal" class="mt-4 space-y-4">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Nilai (0–100)</label>
                            <input wire:model="nilaiInformal" type="number" min="0" max="100" class="block w-32 rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                            @error('nilaiInformal') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Catatan Reviewer</label>
                            <textarea wire:model="catatanInformal" rows="2" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600"></textarea>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Catatan Kesenjangan</label>
                            <textarea wire:model="kesenjanganInformal" rows="2" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600"></textarea>
                        </div>
                        <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan Penilaian Informal</button>
                    </form>
                </div>
            @endif

            <div class="flex justify-end">
                <button wire:click="simpanDanLanjut" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                    Simpan & Matakuliah Berikutnya →
                </button>
            </div>
        </div>

        <!-- Evidence panel -->
        <div class="space-y-6">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Bukti Terlampir</h3>
                <div class="mt-3 space-y-3">
                    @foreach ($pengajuan->bukti as $bukti)
                        <div class="rounded-lg border border-slate-100 p-3">
                            <x-badge :color="$bukti->kategori === 'formal' ? 'indigo' : 'amber'">{{ ucfirst($bukti->kategori) }}</x-badge>
                            <p class="mt-1.5 text-sm font-medium text-slate-900">{{ $bukti->judul }}</p>
                            <p class="text-xs text-slate-500">{{ $bukti->jenis_bukti }} @if($bukti->penerbit) · {{ $bukti->penerbit }} @endif</p>
                            <a href="{{ $bukti->url }}" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-medium text-indigo-600 hover:text-indigo-500">Buka Bukti →</a>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Nilai Mandiri Mahasiswa</h3>
                <p class="mt-2 text-2xl font-bold text-slate-900">{{ $pengajuan->nilai_mandiri_angka }} <span class="text-base font-normal text-slate-500">/ {{ $pengajuan->nilai_mandiri_huruf }}</span></p>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900">Self-Assessment Mahasiswa</h3>
                <ul class="mt-3 space-y-2">
                    @foreach ($pengajuan->selfAssessment as $sa)
                        <li class="flex items-center justify-between text-sm">
                            <span class="text-slate-600">{{ $sa->cpmk->kode }}</span>
                            <x-badge :color="$sa->status === 'mampu' ? 'emerald' : 'rose'">{{ $sa->status === 'mampu' ? 'Mampu' : 'Belum Mampu' }}</x-badge>
                        </li>
                    @endforeach
                </ul>
            </div>

            @if ($pengajuan->mkAsal->isNotEmpty())
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h3 class="text-sm font-semibold text-slate-900">Data MK Asal</h3>
                    <ul class="mt-3 space-y-2 text-sm text-slate-600">
                        @foreach ($pengajuan->mkAsal as $row)
                            <li>
                                <p class="font-medium text-slate-800">{{ $row->kode_asal }} — {{ $row->nama_mk_asal }}</p>
                                <p class="text-xs text-slate-500">{{ $row->cpmk_asal }}</p>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>
</div>
