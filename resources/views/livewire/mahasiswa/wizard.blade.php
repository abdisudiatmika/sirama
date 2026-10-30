<div>
    @php
        $steps = [
            1 => 'Biodata',
            2 => 'Bukti',
            3 => 'Pemetaan MK',
            4 => 'CV & Pernyataan',
            5 => 'Self-Assessment',
            6 => 'MK Asal',
            7 => 'Ringkasan',
        ];
    @endphp

    <x-page-header title="Pengajuan RPL" subtitle="Lengkapi Form 1–6 lalu ajukan berkas Anda untuk dinilai." />

    @if ($mhs->status_berkas === 'dikembalikan' && $pengembalianTerakhir)
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
    @elseif ($readOnly)
        <div class="mb-6 flex items-center gap-3 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" />
            </svg>
            Berkas Anda berstatus <strong class="mx-1">{{ str_replace('_', ' ', $mhs->status_berkas) }}</strong> dan tidak dapat diedit. Hubungi admin prodi bila perlu perubahan.
        </div>
    @endif

    <!-- Step indicator -->
    <div class="mb-8 overflow-x-auto">
        <ol class="flex min-w-max items-center gap-2">
            @foreach ($steps as $n => $label)
                <li class="flex items-center gap-2">
                    <button
                        wire:click="keStep({{ $n }})"
                        @if ($n >= $step) disabled @endif
                        class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-semibold
                            {{ $n === $step ? 'bg-indigo-600 text-white' : ($n < $step ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-400') }}"
                    >
                        {{ $n < $step ? '✓' : $n }}
                    </button>
                    <span class="text-xs font-medium {{ $n === $step ? 'text-slate-900' : 'text-slate-400' }}">{{ $label }}</span>
                    @if ($n < 7)
                        <span class="mx-1 h-px w-6 bg-slate-200"></span>
                    @endif
                </li>
            @endforeach
        </ol>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">

        {{-- ============ STEP 1 — BIODATA ============ --}}
        @if ($step === 1)
            <div>
                <h3 class="text-base font-semibold text-slate-900">Form 1 — Biodata Diri</h3>
                <p class="mt-1 text-sm text-slate-500">Cantumkan data pribadi, data pendidikan formal serta data pekerjaan saudara pada saat ini.</p>

                <form wire:submit="simpanForm1" class="mt-6 space-y-6">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-700">A. Data Pribadi</h4>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Lengkap</label>
                                <input wire:model="nama_lengkap" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                @error('nama_lengkap') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Tempat Lahir</label>
                                <input wire:model="tempat_lahir" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                @error('tempat_lahir') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal Lahir</label>
                                <input wire:model="tanggal_lahir" type="date" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                @error('tanggal_lahir') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Jenis Kelamin</label>
                                <select wire:model="jenis_kelamin" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                    <option value="Laki-laki">Laki-laki</option>
                                    <option value="Perempuan">Perempuan</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Status</label>
                                <select wire:model="status" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                    <option value="Belum Menikah">Belum Menikah</option>
                                    <option value="Menikah">Menikah</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Kebangsaan</label>
                                <input wire:model="kebangsaan" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Kode Pos</label>
                                <input wire:model="kode_pos" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                @error('kode_pos') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Alamat Rumah</label>
                                <textarea wire:model="alamat_rumah" rows="2" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50"></textarea>
                                @error('alamat_rumah') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Telepon Rumah / HP</label>
                                <input wire:model="telp_rumah_hp" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                @error('telp_rumah_hp') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Alamat E-mail</label>
                                <input wire:model="email" type="email" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Alamat Kantor</label>
                                <input wire:model="alamat_kantor" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Telepon Kantor</label>
                                <input wire:model="telp_kantor" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                            </div>
                        </div>
                    </div>

                    <div class="border-t border-slate-100 pt-6">
                        <h4 class="text-sm font-semibold text-slate-700">B. Data Pendidikan Terakhir</h4>
                        <p class="mt-1 text-xs text-slate-500">Fotokopi ijazah dan transkrip nilai dilampirkan sebagai bukti pada Form 2.</p>
                        <div class="mt-3 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Sekolah (SMA/sederajat)</label>
                                <input wire:model="nama_sekolah" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                @error('nama_sekolah') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Tahun Lulus Sekolah</label>
                                <input wire:model="tahun_lulus_sekolah" type="number" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                @error('tahun_lulus_sekolah') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Alamat Sekolah</label>
                                <input wire:model="alamat_sekolah" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Perguruan Tinggi</label>
                                <input wire:model="nama_pt" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Jurusan / Program Studi</label>
                                <input wire:model="jurusan_prodi" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Program</label>
                                <select wire:model="program" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                    <option value="">—</option>
                                    <option value="D3">D3</option>
                                    <option value="D4">D4</option>
                                    <option value="S1">S1</option>
                                    <option value="S2">S2</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Tahun Lulus Perguruan Tinggi</label>
                                <input wire:model="tahun_lulus_pt" type="number" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                            </div>
                        </div>
                    </div>

                    @unless ($readOnly)
                        <div class="flex justify-end gap-3 border-t border-slate-100 pt-6">
                            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan & Lanjut</button>
                        </div>
                    @else
                        <div class="flex justify-end border-t border-slate-100 pt-6">
                            <button type="button" wire:click="$set('step', 2)" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Lanjut</button>
                        </div>
                    @endunless
                </form>
            </div>
        @endif

        {{-- ============ STEP 2 — BUKTI ============ --}}
        @if ($step === 2)
            <div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Form 2 — Pendaftaran Bukti</h3>
                        <p class="mt-1 text-sm text-slate-500">Tambahkan bukti bukti pembelajaran lampau berupa link (Google Drive/YouTube). Tidak ada berkas yang disimpan di server.</p>
                    </div>
                    @unless ($readOnly)
                        <button wire:click="tambahBukti" class="inline-flex flex-shrink-0 items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                            + Tambah Bukti
                        </button>
                    @endunless
                </div>

                <div class="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200">
                    @forelse ($buktiList as $bukti)
                        <div class="flex items-start justify-between gap-4 px-4 py-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <x-badge :color="$bukti->kategori === 'formal' ? 'indigo' : 'amber'">{{ ucfirst($bukti->kategori) }}</x-badge>
                                    <span class="text-sm font-semibold text-slate-900">{{ $bukti->judul }}</span>
                                </div>
                                <p class="mt-1 text-xs text-slate-500">{{ $bukti->jenis_bukti }} @if($bukti->penerbit) · {{ $bukti->penerbit }} @endif @if($bukti->tahun) · {{ $bukti->tahun }} @endif</p>
                                <a href="{{ $bukti->url }}" target="_blank" rel="noopener" class="mt-1 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-500">
                                    Uji Link
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                                </a>
                            </div>
                            @unless ($readOnly)
                                <div class="flex flex-shrink-0 gap-3">
                                    <button wire:click="editBukti({{ $bukti->id }})" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ubah</button>
                                    <button wire:click="hapusBukti({{ $bukti->id }})" wire:confirm="Hapus bukti ini?" class="text-sm font-medium text-rose-600 hover:text-rose-500">Hapus</button>
                                </div>
                            @endunless
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-400">Belum ada bukti ditambahkan.</p>
                    @endforelse
                </div>

                <div class="mt-6 flex justify-between border-t border-slate-100 pt-6">
                    <button wire:click="kembali" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Kembali</button>
                    <button wire:click="lanjutKeForm3" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Lanjut</button>
                </div>
            </div>
        @endif

        {{-- ============ STEP 3 — PEMETAAN MK ============ --}}
        @if ($step === 3)
            <div>
                <h3 class="text-base font-semibold text-slate-900">Form 3 — Pemetaan Matakuliah, Bukti, dan Nilai Mandiri</h3>
                <p class="mt-1 text-sm text-slate-500">Pilih matakuliah yang diajukan untuk direkognisi, lampirkan bukti, dan isi nilai mandiri.</p>

                <div class="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200">
                    @forelse ($matakuliahOptions as $mk)
                        @php $pengajuan = $pengajuanList->get($mk->id); @endphp
                        <div class="px-4 py-3">
                            <div class="flex items-start justify-between gap-4">
                                <label class="flex items-start gap-3">
                                    <input
                                        type="checkbox"
                                        @checked($pengajuan)
                                        @disabled($readOnly)
                                        wire:click="toggleMatakuliah({{ $mk->id }})"
                                        class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600"
                                    >
                                    <div>
                                        <p class="text-sm font-medium text-slate-900">{{ $mk->kode }} — {{ $mk->nama }}</p>
                                        <p class="text-xs text-slate-500">{{ $mk->sks }} SKS · {{ $mk->cpmk->count() }} CPMK</p>
                                    </div>
                                </label>

                                @if ($pengajuan)
                                    <div class="flex flex-shrink-0 items-center gap-3">
                                        @if ($pengajuan->bukti->isNotEmpty() && $pengajuan->nilai_mandiri_angka !== null)
                                            <x-badge color="emerald">{{ $pengajuan->nilai_mandiri_angka }} / {{ $pengajuan->nilai_mandiri_huruf }}</x-badge>
                                        @else
                                            <x-badge color="amber">Belum lengkap</x-badge>
                                        @endif
                                        @unless ($readOnly)
                                            <button wire:click="bukaLengkapi({{ $mk->id }})" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Lengkapi</button>
                                        @endunless
                                    </div>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-400">Belum ada matakuliah aktif pada skema Anda. Hubungi admin prodi.</p>
                    @endforelse
                </div>

                <div class="mt-6 flex justify-between border-t border-slate-100 pt-6">
                    <button wire:click="kembali" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Kembali</button>
                    <button wire:click="lanjutKeForm4" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Lanjut</button>
                </div>
            </div>
        @endif

        {{-- ============ STEP 4 — CV & PERNYATAAN ============ --}}
        @if ($step === 4)
            <div>
                <h3 class="text-base font-semibold text-slate-900">Form 4 — Daftar Riwayat Hidup & Pernyataan Calon Peserta</h3>

                <form wire:submit="simpanForm4" class="mt-6 space-y-6">
                    <div>
                        <a href="{{ route('mahasiswa.template-formulir', 4) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            Unduh Template Daftar Riwayat Hidup (Word)
                        </a>
                        <ol class="mt-3 list-decimal space-y-0.5 pl-5 text-xs text-slate-500">
                            <li>Unduh template di atas. Identitas dan riwayat pendidikan sudah terisi dari data Anda (simpan Form 1 dulu agar terisi).</li>
                            <li>Lengkapi pekerjaan, pelatihan, seminar, penghargaan, organisasi, dan riwayat pekerjaan, lalu tanda tangani.</li>
                            <li>Unggah dokumen yang sudah ditandatangani ke Google Drive, lalu tempel link-nya di bawah.</li>
                        </ol>

                        <div class="mt-4">
                            <label class="mb-1.5 block text-sm font-medium text-slate-700">Link Daftar Riwayat Hidup yang Sudah Diisi & Ditandatangani</label>
                            <input wire:model="cv_url" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50" placeholder="https://drive.google.com/...">
                            @error('cv_url') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            <p class="mt-1.5 text-xs text-slate-500">Atur izin akses link minimal <em>"siapa saja yang memiliki link dapat melihat"</em> dan izinkan unduhan.</p>
                            @if ($cv_url)
                                <a href="{{ $cv_url }}" target="_blank" rel="noopener" class="mt-1.5 inline-block text-xs font-medium text-indigo-600 hover:text-indigo-500">Uji Link →</a>
                            @endif
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                        <h4 class="text-sm font-semibold text-slate-800">Pernyataan Calon Peserta</h4>
                        <p class="mt-2 text-sm text-slate-600">Dengan ini saya, <strong>{{ $nama_lengkap ?: $mhs->user->name }}</strong>, calon peserta Rekognisi Pembelajaran Lampau pada Program Studi <strong>{{ $mhs->prodi->nama }}</strong> pada Perguruan Tinggi <strong>Politeknik Negeri Bali</strong>, menyatakan:</p>
                        <ol class="mt-2 list-decimal space-y-1.5 pl-5 text-sm text-slate-600">
                            <li>Seluruh informasi yang saya berikan adalah benar dan saya bersedia menerima sanksi bila terbukti tidak benar.</li>
                            <li>Memberi izin kepada Tim RPL untuk memeriksa kebenaran informasi kepada institusi pendidikan dan tempat kerja terkait.</li>
                            <li>Bersedia melengkapi berkas yang diperlukan untuk proses asesmen dan rekognisi.</li>
                            <li>Bersedia mengikuti proses asesmen sesuai jadwal yang ditetapkan dan melunasi biaya pendaftaran.</li>
                        </ol>

                        <label class="mt-4 flex items-start gap-2 text-sm font-medium text-slate-800">
                            <input wire:model="pernyataan_setuju" type="checkbox" @disabled($readOnly) class="mt-0.5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                            Saya menyetujui pernyataan di atas.
                        </label>
                        @error('pernyataan_setuju') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror

                        <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Tempat</label>
                                <input wire:model="pernyataan_tempat" type="text" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                                @error('pernyataan_tempat') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-slate-700">Tanggal</label>
                                <input wire:model="pernyataan_tanggal" type="date" @disabled($readOnly) class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600 disabled:bg-slate-50">
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between border-t border-slate-100 pt-6">
                        <button type="button" wire:click="kembali" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Kembali</button>
                        @unless ($readOnly)
                            <button type="submit" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan & Lanjut</button>
                        @else
                            <button type="button" wire:click="$set('step', 5)" class="rounded-lg bg-slate-100 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-200">Lanjut</button>
                        @endunless
                    </div>
                </form>
            </div>
        @endif

        {{-- ============ STEP 5 — SELF ASSESSMENT ============ --}}
        @if ($step === 5)
            @php
                $totalCpmk = $pengajuanList->sum(fn ($p) => $p->selfAssessment->count());
                $terisiCpmk = $pengajuanList->sum(fn ($p) => $p->selfAssessment->whereNotNull('status')->count());
            @endphp
            <div>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-slate-900">Form 5 — Self-Assessment CPMK</h3>
                        <p class="mt-1 text-sm text-slate-500">Progres: {{ $terisiCpmk }}/{{ $totalCpmk }} CPMK terisi</p>
                    </div>
                    @unless ($readOnly)
                        <button wire:click="centangSemuaMampuGlobal" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50">Centang Semua "Mampu"</button>
                    @endunless
                </div>

                <div class="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200">
                    @foreach ($pengajuanList as $pengajuan)
                        @php
                            $totalMk = $pengajuan->selfAssessment->count();
                            $terisiMk = $pengajuan->selfAssessment->whereNotNull('status')->count();
                        @endphp
                        <button
                            type="button"
                            wire:click="bukaSelfAssessment({{ $pengajuan->id }})"
                            class="flex w-full items-center justify-between gap-4 px-4 py-3 text-left hover:bg-slate-50"
                        >
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $pengajuan->matakuliah->kode }} — {{ $pengajuan->matakuliah->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $totalMk }} CPMK</p>
                            </div>
                            <div class="flex flex-shrink-0 items-center gap-3">
                                <x-badge :color="$terisiMk === $totalMk ? 'emerald' : 'slate'">{{ $terisiMk }}/{{ $totalMk }} terisi</x-badge>
                                <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                            </div>
                        </button>
                    @endforeach
                </div>

                <div class="mt-6 flex justify-between border-t border-slate-100 pt-6">
                    <button wire:click="kembali" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Kembali</button>
                    <button wire:click="lanjutKeForm6" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Lanjut</button>
                </div>
            </div>
        @endif

        {{-- ============ STEP 6 — MK ASAL ============ --}}
        @if ($step === 6)
            <div>
                <h3 class="text-base font-semibold text-slate-900">Form 6 — Data Matakuliah Asal</h3>
                <p class="mt-1 text-sm text-slate-500">Hanya matakuliah dengan bukti formal (Ijazah / Transkrip Nilai) yang perlu diisi di sini.</p>

                <div class="mt-6 divide-y divide-slate-100 rounded-xl border border-slate-200">
                    @forelse ($pengajuanBerbuktiFormal as $pengajuan)
                        <div class="flex items-center justify-between gap-4 px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $pengajuan->matakuliah->kode }} — {{ $pengajuan->matakuliah->nama }}</p>
                                <p class="text-xs text-slate-500">{{ $pengajuan->mkAsal->count() }} baris MK asal</p>
                            </div>
                            @unless ($readOnly)
                                <button wire:click="bukaMkAsal({{ $pengajuan->id }})" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Kelola</button>
                            @endunless
                        </div>
                    @empty
                        <p class="px-4 py-8 text-center text-sm text-slate-400">Tidak ada matakuliah berbukti formal — lanjutkan ke ringkasan.</p>
                    @endforelse
                </div>

                <div class="mt-6 flex justify-between border-t border-slate-100 pt-6">
                    <button wire:click="kembali" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Kembali</button>
                    <button wire:click="lanjutKeRingkasan" class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Lanjut ke Ringkasan</button>
                </div>
            </div>
        @endif

        {{-- ============ STEP 7 — RINGKASAN ============ --}}
        @if ($step === 7)
            <div>
                <h3 class="text-base font-semibold text-slate-900">Ringkasan Pengajuan</h3>

                @if ($mhs->status_berkas === 'draft' || $mhs->status_berkas === 'dikembalikan')
                    <p class="mt-1 text-sm text-slate-500">Periksa kembali seluruh isian sebelum mengajukan. Setelah submit, berkas akan terkunci.</p>
                @else
                    <p class="mt-1 text-sm text-emerald-600">Berkas Anda sudah diajukan dan sedang/telah diproses oleh asesor.</p>
                @endif

                <div class="mt-6 space-y-4">
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase text-slate-500">Biodata</p>
                        <p class="mt-1 text-sm text-slate-800">{{ $nama_lengkap }} · {{ $email }} · {{ $telp_rumah_hp }}</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase text-slate-500">Bukti Terdaftar</p>
                        <p class="mt-1 text-sm text-slate-800">{{ $buktiList->count() }} bukti</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 p-4">
                        <p class="text-xs font-medium uppercase text-slate-500">Matakuliah Diajukan</p>
                        <p class="mt-1 text-sm text-slate-800">{{ $pengajuanList->count() }} matakuliah</p>
                        <ul class="mt-2 space-y-1 text-sm text-slate-600">
                            @foreach ($pengajuanList as $p)
                                <li>{{ $p->matakuliah->kode }} — {{ $p->matakuliah->nama }} (nilai mandiri {{ $p->nilai_mandiri_angka }}/{{ $p->nilai_mandiri_huruf }})</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="mt-6 flex justify-between border-t border-slate-100 pt-6">
                    <button wire:click="kembali" class="rounded-lg px-5 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Kembali</button>
                    @if ($mhs->status_berkas === 'draft' || $mhs->status_berkas === 'dikembalikan')
                        <button wire:click="submitFinal" wire:confirm="Setelah diajukan, berkas akan terkunci dan tidak bisa diedit. Lanjutkan?" class="rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">Submit Final</button>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- ============ MODAL: BUKTI ============ --}}
    <x-modal :show="$showBuktiModal" :title="$editingBuktiId ? 'Ubah Bukti' : 'Tambah Bukti'" max-width="xl">
        <form wire:submit="simpanBukti" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Kategori</label>
                    <select wire:model.live="bukti_kategori" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        <option value="formal">Formal</option>
                        <option value="informal">Informal</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Jenis Bukti</label>
                    <select wire:model="bukti_jenis" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        @foreach ($jenisBuktiOptions[$bukti_kategori] as $jenis)
                            <option value="{{ $jenis }}">{{ $jenis }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Judul Bukti</label>
                <input wire:model="bukti_judul" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                @error('bukti_judul') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Penerbit / Instansi</label>
                    <input wire:model="bukti_penerbit" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Tahun</label>
                    <input wire:model="bukti_tahun" type="number" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">URL (Google Drive / YouTube)</label>
                <input wire:model="bukti_url" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="https://drive.google.com/...">
                @error('bukti_url') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Keterangan Singkat</label>
                <textarea wire:model="bukti_keterangan" rows="2" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600"></textarea>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="closeBuktiModal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
            </div>
        </form>
    </x-modal>

    {{-- ============ MODAL: LENGKAPI PENGAJUAN MK ============ --}}
    <x-modal :show="$showLengkapiModal" title="Lengkapi Pengajuan Matakuliah" max-width="xl" onclose="closeLengkapiModal">
        <div class="space-y-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Pilih Bukti Pendukung</label>
                <div class="max-h-48 space-y-1 overflow-y-auto rounded-lg border border-slate-200 p-3">
                    @forelse ($lengkapiBuktiOptions as $bukti)
                        <label class="flex items-center gap-2 text-sm text-slate-700">
                            <input type="checkbox" wire:model="lengkapiBuktiIds" value="{{ $bukti->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-600">
                            <x-badge :color="$bukti->kategori === 'formal' ? 'indigo' : 'amber'">{{ ucfirst($bukti->kategori) }}</x-badge>
                            {{ $bukti->judul }}
                        </label>
                    @empty
                        <p class="text-sm text-slate-400">Belum ada bukti. Tambahkan di Form 2 terlebih dahulu.</p>
                    @endforelse
                </div>
                @error('lengkapiBuktiIds') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nilai Mandiri (Angka)</label>
                    <input wire:model="lengkapiNilaiAngka" type="number" min="0" max="100" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('lengkapiNilaiAngka') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nilai Mandiri (Huruf)</label>
                    <select wire:model="lengkapiNilaiHuruf" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        @foreach ($hurufOptions as $h)
                            <option value="{{ $h }}">{{ $h }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="closeLengkapiModal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button wire:click="simpanLengkapi" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
            </div>
        </div>
    </x-modal>

    {{-- ============ MODAL: SELF ASSESSMENT ============ --}}
    @php
        $selfAssessmentPengajuan = $selfAssessmentPengajuanId ? $pengajuanList->firstWhere('id', $selfAssessmentPengajuanId) : null;
    @endphp
    <x-modal :show="$showSelfAssessmentModal" :title="$selfAssessmentPengajuan ? $selfAssessmentPengajuan->matakuliah->kode.' — '.$selfAssessmentPengajuan->matakuliah->nama : 'Self-Assessment CPMK'" max-width="2xl" onclose="closeSelfAssessmentModal">
        @if ($selfAssessmentPengajuan)
            <div class="space-y-4">
                @unless ($readOnly)
                    <div class="flex justify-end">
                        <button type="button" wire:click="centangSemuaMampu({{ $selfAssessmentPengajuan->id }})" class="text-xs font-medium text-indigo-600 hover:text-indigo-500">Centang Semua "Mampu"</button>
                    </div>
                @endunless

                <div class="max-h-96 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-100">
                    @foreach ($selfAssessmentPengajuan->selfAssessment as $sa)
                        <div class="flex items-center justify-between gap-4 px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-slate-900">{{ $sa->cpmk->kode }}</p>
                                <p class="text-xs text-slate-500">{{ $sa->cpmk->deskripsi }}</p>
                            </div>
                            <div class="flex flex-shrink-0 gap-2">
                                <button
                                    type="button"
                                    wire:click="setSelfAssessment({{ $sa->id }}, 'mampu')"
                                    @disabled($readOnly)
                                    class="rounded-lg px-3 py-1.5 text-xs font-medium {{ $sa->status === 'mampu' ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-600' }}"
                                >Mampu</button>
                                <button
                                    type="button"
                                    wire:click="setSelfAssessment({{ $sa->id }}, 'belum_mampu')"
                                    @disabled($readOnly)
                                    class="rounded-lg px-3 py-1.5 text-xs font-medium {{ $sa->status === 'belum_mampu' ? 'bg-rose-600 text-white' : 'bg-slate-100 text-slate-600' }}"
                                >Belum Mampu</button>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end pt-2">
                    <button type="button" wire:click="closeSelfAssessmentModal" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Selesai</button>
                </div>
            </div>
        @endif
    </x-modal>

    {{-- ============ MODAL: MK ASAL ============ --}}
    <x-modal :show="$showMkAsalModal" title="Data Matakuliah Asal" max-width="2xl" onclose="closeMkAsalModal">
        <div class="space-y-4">
            <form wire:submit="tambahMkAsalRow" class="space-y-3 rounded-xl border border-slate-100 bg-slate-50 p-4">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Kode MK Asal</label>
                        <input wire:model="mkAsalKode" type="text" class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="MK001">
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-slate-600">Nama MK Asal</label>
                        <input wire:model="mkAsalNama" type="text" class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="Pendidikan Pancasila">
                    </div>
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-slate-600">CPMK Asal</label>
                    <textarea wire:model="mkAsalCpmk" rows="2" class="block w-full rounded-lg border-0 px-3 py-2 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="Mampu menjelaskan..."></textarea>
                </div>
                @error('mkAsalKode') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                @error('mkAsalNama') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                @error('mkAsalCpmk') <p class="text-sm text-rose-600">{{ $message }}</p> @enderror
                <div class="flex justify-end gap-2">
                    @if ($editingMkAsalId)
                        <button type="button" wire:click="batalEditMkAsal" class="rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-slate-100">Batal</button>
                    @endif
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">{{ $editingMkAsalId ? 'Simpan' : 'Tambah Baris' }}</button>
                </div>
            </form>

            <div class="max-h-64 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-100">
                @php
                    $rows = $mkAsalPengajuanMkId ? \App\Models\MkAsal::where('pengajuan_mk_id', $mkAsalPengajuanMkId)->get() : collect();
                @endphp
                @forelse ($rows as $row)
                    <div class="flex items-start justify-between gap-3 px-4 py-3">
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $row->kode_asal }} — {{ $row->nama_mk_asal }}</p>
                            <p class="text-sm text-slate-600">{{ $row->cpmk_asal }}</p>
                        </div>
                        <div class="flex flex-shrink-0 gap-3">
                            <button wire:click="editMkAsalRow({{ $row->id }})" class="text-sm font-medium text-indigo-600 hover:text-indigo-500">Ubah</button>
                            <button wire:click="hapusMkAsalRow({{ $row->id }})" wire:confirm="Hapus baris ini?" class="text-sm font-medium text-rose-600 hover:text-rose-500">Hapus</button>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-8 text-center text-sm text-slate-400">Belum ada baris MK asal.</p>
                @endforelse
            </div>
        </div>
    </x-modal>
</div>
