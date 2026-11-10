<div>
    <x-page-header title="Mahasiswa" subtitle="Kelola akun mahasiswa RPL, skema yang diikuti, dan penugasan asesor.">
        <x-slot:actions>
            <x-prodi-switcher :options="$prodiOptions" :selected="$prodiId" />
            <button wire:click="tambah" class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Mahasiswa
            </button>
        </x-slot:actions>
    </x-page-header>

    @if ($generatedPassword)
        <div class="mb-6 flex items-start justify-between gap-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3.5 text-sm text-amber-900">
            <div>
                <p class="font-medium">Password awal untuk <span class="font-mono">{{ $generatedFor }}</span>:</p>
                <p class="mt-1 select-all rounded-lg bg-white px-3 py-1.5 font-mono text-base font-semibold tracking-wide text-amber-800 ring-1 ring-amber-200">{{ $generatedPassword }}</p>
                <p class="mt-1.5 text-xs text-amber-700">Catat & sampaikan ke pemilik akun sekarang — password ini tidak akan ditampilkan lagi.</p>
            </div>
            <button wire:click="tutupInfoPassword" class="text-amber-500 hover:text-amber-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <div class="mb-4">
        <input wire:model.live.debounce.400ms="search" type="text" placeholder="Cari nama atau NIM..." class="w-full max-w-sm rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">NIM</th>
                        <th class="px-6 py-3">Nama</th>
                        <th class="px-6 py-3">Skema</th>
                        <th class="px-6 py-3">Asesor</th>
                        <th class="px-6 py-3">Status Berkas</th>
                        <th class="px-6 py-3">Akses</th>
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
                            <td class="px-6 py-3">
                                <button wire:click="bukaPanel({{ $mhs->id }})" class="text-left font-medium text-slate-900 hover:text-indigo-600 hover:underline">{{ $mhs->user->name }}</button>
                            </td>
                            <td class="px-6 py-3 text-slate-600">{{ $mhs->skema->nama ?? '—' }}</td>
                            <td class="px-6 py-3">
                                <button wire:click="kelolaAsesor({{ $mhs->id }})" class="font-medium text-indigo-600 hover:text-indigo-500">
                                    {{ $mhs->penugasanAsesor->count() }}/3 ditugaskan
                                </button>
                            </td>
                            <td class="px-6 py-3">
                                <x-badge :color="$statusLabel[$mhs->status_berkas][1]">{{ $statusLabel[$mhs->status_berkas][0] }}</x-badge>
                            </td>
                            <td class="px-6 py-3">
                                <button wire:click="toggleLock({{ $mhs->id }})">
                                    <x-badge :color="$mhs->user->is_locked ? 'rose' : 'emerald'">{{ $mhs->user->is_locked ? 'Terkunci' : 'Aktif' }}</x-badge>
                                </button>
                            </td>
                            <td class="px-6 py-3">
                                <div class="flex justify-end gap-3">
                                    @if (in_array($mhs->status_berkas, ['draft', 'dikembalikan']))
                                        <button
                                            wire:click="paksaKirimKeAsesor({{ $mhs->id }})"
                                            wire:confirm="Kirim berkas ini ke asesor sekarang? Kelengkapan data (Form 1-6) TIDAK akan divalidasi ulang — pastikan sudah sesuai secara manual."
                                            class="font-medium text-emerald-600 hover:text-emerald-700"
                                        >Kirim ke Asesor</button>
                                    @endif
                                    @if (in_array($mhs->status_berkas, ['diajukan', 'sedang_dinilai', 'selesai_dinilai']))
                                        <button wire:click="bukaPengembalian({{ $mhs->id }})" class="font-medium text-amber-600 hover:text-amber-700">Kembalikan Berkas</button>
                                    @endif
                                    <button wire:click="resetPassword({{ $mhs->id }})" wire:confirm="Buat password baru untuk akun ini?" class="font-medium text-slate-600 hover:text-slate-800">Reset Password</button>
                                    <button wire:click="edit({{ $mhs->id }})" class="font-medium text-indigo-600 hover:text-indigo-500">Ubah</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">Belum ada mahasiswa untuk prodi ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginasi :paginator="$mahasiswaList" />
    </div>

    <x-modal :show="$showModal" :title="$editingId ? 'Ubah Mahasiswa' : 'Tambah Mahasiswa'" max-width="xl">
        <form wire:submit="simpan" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Nama Lengkap</label>
                    <input wire:model="name" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('name') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">NIM</label>
                    <input wire:model="nim" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('nim') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Username</label>
                    <input wire:model="username" type="text" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('username') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Email</label>
                    <input wire:model="email" type="email" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('email') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Tahun Masuk</label>
                    <input wire:model="tahun_masuk" type="number" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                    @error('tahun_masuk') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Skema</label>
                    <select wire:model="skema_id" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        <option value="">— Pilih skema —</option>
                        @foreach ($skemaOptions as $skema)
                            <option value="{{ $skema->id }}">{{ $skema->nama }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @unless ($editingId)
                <p class="text-xs text-slate-500">Password awal akan dibuat otomatis dan ditampilkan setelah akun disimpan.</p>
            @endunless

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="closeModal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan</button>
            </div>
        </form>
    </x-modal>

    <x-modal :show="$showAsesorModal" title="Penugasan Asesor" max-width="xl" onclose="closeAsesorModal">
        <div class="space-y-4">
            <p class="text-sm text-slate-500">Pilih hingga 3 asesor untuk menilai berkas RPL mahasiswa ini.</p>

            <input wire:model.live.debounce.300ms="asesorSearch" type="text" placeholder="Cari asesor..." class="w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">

            @for ($urutan = 1; $urutan <= 3; $urutan++)
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700">Asesor {{ $urutan }}</label>
                    <select wire:model="slotAsesor.{{ $urutan }}" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600">
                        <option value="">— Tidak ditugaskan —</option>
                        @foreach ($asesorOptions as $asesor)
                            <option value="{{ $asesor->id }}">{{ $asesor->user->name }} @if($asesor->bidang_keahlian) ({{ $asesor->bidang_keahlian }}) @endif</option>
                        @endforeach
                    </select>
                </div>
            @endfor

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="closeAsesorModal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button wire:click="simpanAsesor" class="rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">Simpan Penugasan</button>
            </div>
        </div>
    </x-modal>

    <x-modal :show="$showPengembalianModal" title="Kembalikan Berkas ke Mahasiswa" max-width="xl" onclose="closePengembalianModal">
        <div class="space-y-4">
            <p class="text-sm text-slate-500">Berkas akan dibuka kembali untuk diedit mahasiswa. Alasan ini akan tercatat dan ditampilkan kepada mahasiswa.</p>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700">Alasan Pengembalian</label>
                <textarea wire:model="alasanPengembalian" rows="4" class="block w-full rounded-lg border-0 px-3.5 py-2.5 text-sm text-slate-900 ring-1 ring-inset ring-slate-300 focus:ring-2 focus:ring-inset focus:ring-indigo-600" placeholder="Contoh: Bukti pengalaman kerja untuk MK Akuntansi Keuangan 1 belum sesuai, mohon lampirkan surat keterangan kerja yang valid."></textarea>
                @error('alasanPengembalian') <p class="mt-1.5 text-sm text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <button type="button" wire:click="closePengembalianModal" class="rounded-lg px-4 py-2.5 text-sm font-semibold text-slate-600 hover:bg-slate-100">Batal</button>
                <button wire:click="simpanPengembalian" wire:confirm="Berkas akan dibuka kembali untuk mahasiswa edit. Lanjutkan?" class="rounded-lg bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-500">Kembalikan Berkas</button>
            </div>
        </div>
    </x-modal>

    @if ($panelMahasiswa)
        <x-slide-over :show="true" :title="$panelMahasiswa->user->name" :subtitle="'NIM '.$panelMahasiswa->nim.' · '.($panelMahasiswa->skema->nama ?? 'Belum ada skema')">
            <div class="mb-6 flex items-center gap-2">
                <span class="text-sm text-slate-500">Status berkas</span>
                <x-badge :color="$statusLabel[$panelMahasiswa->status_berkas][1]">{{ $statusLabel[$panelMahasiswa->status_berkas][0] }}</x-badge>
            </div>

            <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">Formulir RPL</h4>
            <ul class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                @foreach ($panelFormulir as $form)
                    @php $ada = $form['unduhan'] || $form['link']; @endphp
                    <li class="flex items-center gap-3 px-4 py-3">
                        <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg text-sm font-semibold {{ $ada ? 'bg-indigo-50 text-indigo-700' : 'bg-slate-100 text-slate-400' }}">{{ $form['nomor'] }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium {{ $ada ? 'text-slate-900' : 'text-slate-400' }}">Form {{ $form['nomor'] }} — {{ $form['judul'] }}</p>
                            @if ($form['catatan'])
                                <p class="text-xs {{ $ada ? 'text-amber-600' : 'text-slate-400' }}">{{ $form['catatan'] }}</p>
                            @endif
                        </div>
                        <div class="flex max-w-[10rem] flex-shrink-0 flex-wrap justify-end gap-1.5">
                        @if ($form['link'])
                            <a href="{{ $form['link'] }}" target="_blank" rel="noopener noreferrer" title="Dokumen bertanda tangan yang dikirim mahasiswa" class="inline-flex flex-shrink-0 items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                                </svg>
                                Buka
                            </a>
                        @endif
                        @foreach ($form['unduhan'] as $unduh)
                            <a href="{{ $unduh['url'] }}" class="inline-flex flex-shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                </svg>
                                {{ $unduh['label'] }}
                            </a>
                        @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>

            @php $jumlahDiakui = $panelMahasiswa->hasilPleno->where('status', 'Direkognisi')->count(); @endphp
            <h4 class="mb-3 mt-6 text-xs font-semibold uppercase tracking-wide text-slate-500">Unggah SIAKAD</h4>
            <div class="flex items-center gap-3 rounded-xl border border-slate-200 px-4 py-3">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-900">Mata Kuliah RPL (format SIAKAD)</p>
                    <p class="text-xs {{ $jumlahDiakui ? 'text-slate-500' : 'text-amber-600' }}">
                        {{ $jumlahDiakui ? $jumlahDiakui.' mata kuliah berstatus Direkognisi' : 'Belum ada mata kuliah berstatus Direkognisi (hasil pleno), file hanya berisi judul kolom' }}
                    </p>
                </div>
                <a href="{{ route('admin-prodi.mahasiswa.siakad', $panelMahasiswa) }}" class="inline-flex flex-shrink-0 items-center gap-1.5 whitespace-nowrap rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Excel
                </a>
            </div>

            <x-slot:footer>
                <a href="{{ route('admin-prodi.mahasiswa.dokumen-zip', $panelMahasiswa) }}" class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Unduh Semua (.zip)
                </a>
                <p class="mt-2 text-center text-xs text-slate-400">Berisi semua formulir yang tersedia, plus daftar link dokumen mahasiswa.</p>
            </x-slot:footer>
        </x-slide-over>
    @endif
</div>
