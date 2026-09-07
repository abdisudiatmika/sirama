<?php

namespace App\Livewire\Mahasiswa;

use App\Models\Bukti;
use App\Models\Cpmk;
use App\Models\Mahasiswa;
use App\Models\MkAsal;
use App\Models\PengajuanMk;
use App\Models\Pernyataan;
use App\Models\SelfAssessment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Pengajuan RPL')]
class Wizard extends Component
{
    public int $step = 1;

    public int $mahasiswaId;

    // Form 1 — Biodata
    public string $nama_lengkap = '';

    public string $tempat_lahir = '';

    public ?string $tanggal_lahir = null;

    public string $jenis_kelamin = 'Laki-laki';

    public string $status = 'Belum Menikah';

    public string $kebangsaan = 'Indonesia';

    public string $alamat_rumah = '';

    public string $kode_pos = '';

    public string $telp_rumah_hp = '';

    public string $alamat_kantor = '';

    public string $telp_kantor = '';

    public string $email = '';

    // Form 1 — Pendidikan Terakhir
    public string $nama_sekolah = '';

    public string $alamat_sekolah = '';

    public ?int $tahun_lulus_sekolah = null;

    public string $nama_pt = '';

    public string $jurusan_prodi = '';

    public ?string $program = null;

    public ?int $tahun_lulus_pt = null;

    // Form 2 — Bukti
    public bool $showBuktiModal = false;

    public ?int $editingBuktiId = null;

    public string $bukti_kategori = 'formal';

    public string $bukti_jenis = 'Ijazah';

    public string $bukti_judul = '';

    public string $bukti_penerbit = '';

    public ?int $bukti_tahun = null;

    public string $bukti_url = '';

    public string $bukti_keterangan = '';

    // Form 3 — Pemetaan MK
    public bool $showLengkapiModal = false;

    public ?int $lengkapiMatakuliahId = null;

    /** @var array<int> */
    public array $lengkapiBuktiIds = [];

    public ?int $lengkapiNilaiAngka = null;

    public string $lengkapiNilaiHuruf = 'A';

    // Form 4 — CV & Pernyataan
    public string $cv_url = '';

    public string $cv_keterangan = '';

    public bool $pernyataan_setuju = false;

    public string $pernyataan_tempat = '';

    public ?string $pernyataan_tanggal = null;

    // Form 6 — MK Asal
    public bool $showMkAsalModal = false;

    public ?int $mkAsalPengajuanMkId = null;

    public string $mkAsalKode = '';

    public string $mkAsalNama = '';

    public string $mkAsalCpmk = '';

    public ?int $editingMkAsalId = null;

    public array $jenisBuktiOptions = [
        'formal' => ['Ijazah', 'Transkrip Nilai'],
        'informal' => ['Video', 'Surat Pengalaman Kerja'],
    ];

    public array $hurufOptions = ['A', 'AB', 'B', 'BC', 'C', 'D', 'E'];

    public function mount(): void
    {
        $mahasiswa = Auth::user()->mahasiswa;
        abort_unless($mahasiswa, 403);
        $this->mahasiswaId = $mahasiswa->id;

        if ($mahasiswa->status_berkas !== 'draft' && $mahasiswa->status_berkas !== 'dikembalikan') {
            $this->step = 7;
        }

        $this->loadForm1();
        $this->loadForm4();
    }

    private function mhs(): Mahasiswa
    {
        return Mahasiswa::with('skema.matakuliah.cpmk')->findOrFail($this->mahasiswaId);
    }

    private function isReadOnly(): bool
    {
        return ! in_array($this->mhs()->status_berkas, ['draft', 'dikembalikan']);
    }

    // ================= Form 1 =================

    private function loadForm1(): void
    {
        $mhs = $this->mhs();
        $biodata = $mhs->biodata;
        $pendidikan = $mhs->pendidikanTerakhir;

        if ($biodata) {
            $this->nama_lengkap = $biodata->nama_lengkap;
            $this->tempat_lahir = $biodata->tempat_lahir;
            $this->tanggal_lahir = $biodata->tanggal_lahir?->format('Y-m-d');
            $this->jenis_kelamin = $biodata->jenis_kelamin;
            $this->status = $biodata->status;
            $this->kebangsaan = $biodata->kebangsaan;
            $this->alamat_rumah = $biodata->alamat_rumah;
            $this->kode_pos = (string) $biodata->kode_pos;
            $this->telp_rumah_hp = $biodata->telp_rumah_hp;
            $this->alamat_kantor = (string) $biodata->alamat_kantor;
            $this->telp_kantor = (string) $biodata->telp_kantor;
            $this->email = $biodata->email;
        } else {
            $this->email = $mhs->user->email;
            $this->nama_lengkap = $mhs->user->name;
        }

        if ($pendidikan) {
            $this->nama_sekolah = $pendidikan->nama_sekolah;
            $this->alamat_sekolah = (string) $pendidikan->alamat_sekolah;
            $this->tahun_lulus_sekolah = $pendidikan->tahun_lulus_sekolah;
            $this->nama_pt = (string) $pendidikan->nama_pt;
            $this->jurusan_prodi = (string) $pendidikan->jurusan_prodi;
            $this->program = $pendidikan->program;
            $this->tahun_lulus_pt = $pendidikan->tahun_lulus_pt;
        }
    }

    protected function rulesForm1(): array
    {
        return [
            'nama_lengkap' => ['required', 'string', 'max:150'],
            'tempat_lahir' => ['required', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'jenis_kelamin' => ['required', 'in:Laki-laki,Perempuan'],
            'status' => ['required', 'in:Belum Menikah,Menikah,Lainnya'],
            'kebangsaan' => ['required', 'string', 'max:50'],
            'alamat_rumah' => ['required', 'string'],
            'kode_pos' => ['nullable', 'digits:5'],
            'telp_rumah_hp' => ['required', 'regex:/^[0-9+ ]+$/'],
            'alamat_kantor' => ['nullable', 'string'],
            'telp_kantor' => ['nullable', 'regex:/^[0-9+ ]+$/'],
            'email' => ['required', 'email'],
            'nama_sekolah' => ['required', 'string', 'max:150'],
            'alamat_sekolah' => ['nullable', 'string'],
            'tahun_lulus_sekolah' => ['required', 'integer', 'min:1970', 'max:'.now()->year],
            'nama_pt' => ['nullable', 'string', 'max:150'],
            'jurusan_prodi' => ['nullable', 'string', 'max:150'],
            'program' => ['nullable', 'in:D3,D4,S1,S2'],
            'tahun_lulus_pt' => ['nullable', 'integer', 'min:1970', 'max:'.now()->year],
        ];
    }

    public function simpanForm1(bool $lanjut = true): void
    {
        $data = $this->validate($this->rulesForm1());

        $mhs = $this->mhs();

        $mhs->biodata()->updateOrCreate(['mahasiswa_id' => $mhs->id], [
            'nama_lengkap' => $data['nama_lengkap'],
            'tempat_lahir' => $data['tempat_lahir'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'status' => $data['status'],
            'kebangsaan' => $data['kebangsaan'],
            'alamat_rumah' => $data['alamat_rumah'],
            'kode_pos' => $data['kode_pos'],
            'telp_rumah_hp' => $data['telp_rumah_hp'],
            'alamat_kantor' => $data['alamat_kantor'],
            'telp_kantor' => $data['telp_kantor'],
            'email' => $data['email'],
        ]);

        $mhs->pendidikanTerakhir()->updateOrCreate(['mahasiswa_id' => $mhs->id], [
            'nama_sekolah' => $data['nama_sekolah'],
            'alamat_sekolah' => $data['alamat_sekolah'],
            'tahun_lulus_sekolah' => $data['tahun_lulus_sekolah'],
            'nama_pt' => $data['nama_pt'],
            'jurusan_prodi' => $data['jurusan_prodi'],
            'program' => $data['program'],
            'tahun_lulus_pt' => $data['tahun_lulus_pt'],
        ]);

        session()->flash('success', 'Form 1 tersimpan.');

        if ($lanjut) {
            $this->step = 2;
        }
    }

    // ================= Form 2 — Bukti =================

    public function updatedBuktiKategori(): void
    {
        $this->bukti_jenis = $this->jenisBuktiOptions[$this->bukti_kategori][0];
    }

    public function tambahBukti(): void
    {
        $this->resetBuktiForm();
        $this->showBuktiModal = true;
    }

    public function editBukti(int $id): void
    {
        $bukti = Bukti::where('mahasiswa_id', $this->mahasiswaId)->findOrFail($id);
        $this->editingBuktiId = $bukti->id;
        $this->bukti_kategori = $bukti->kategori;
        $this->bukti_jenis = $bukti->jenis_bukti;
        $this->bukti_judul = $bukti->judul;
        $this->bukti_penerbit = (string) $bukti->penerbit;
        $this->bukti_tahun = $bukti->tahun;
        $this->bukti_url = $bukti->url;
        $this->bukti_keterangan = (string) $bukti->keterangan;
        $this->showBuktiModal = true;
    }

    public function simpanBukti(): void
    {
        $data = $this->validate([
            'bukti_kategori' => ['required', 'in:formal,informal'],
            'bukti_jenis' => ['required', 'string'],
            'bukti_judul' => ['required', 'string', 'max:150'],
            'bukti_penerbit' => ['nullable', 'string', 'max:150'],
            'bukti_tahun' => ['nullable', 'integer', 'min:1970', 'max:'.now()->year],
            'bukti_url' => ['required', 'url', 'max:500'],
            'bukti_keterangan' => ['nullable', 'string'],
        ]);

        Bukti::updateOrCreate(
            ['id' => $this->editingBuktiId, 'mahasiswa_id' => $this->mahasiswaId],
            [
                'mahasiswa_id' => $this->mahasiswaId,
                'kategori' => $data['bukti_kategori'],
                'jenis_bukti' => $data['bukti_jenis'],
                'judul' => $data['bukti_judul'],
                'penerbit' => $data['bukti_penerbit'],
                'tahun' => $data['bukti_tahun'],
                'url' => $data['bukti_url'],
                'keterangan' => $data['bukti_keterangan'],
            ]
        );

        $this->closeBuktiModal();
    }

    public function hapusBukti(int $id): void
    {
        Bukti::where('mahasiswa_id', $this->mahasiswaId)->where('id', $id)->delete();
    }

    public function closeBuktiModal(): void
    {
        $this->showBuktiModal = false;
        $this->resetBuktiForm();
    }

    private function resetBuktiForm(): void
    {
        $this->reset(['editingBuktiId', 'bukti_judul', 'bukti_penerbit', 'bukti_tahun', 'bukti_url', 'bukti_keterangan']);
        $this->bukti_kategori = 'formal';
        $this->bukti_jenis = 'Ijazah';
        $this->resetErrorBag();
    }

    public function lanjutKeForm3(): void
    {
        if (Bukti::where('mahasiswa_id', $this->mahasiswaId)->count() < 1) {
            session()->flash('error', 'Minimal 1 bukti harus terdaftar sebelum lanjut.');

            return;
        }

        $this->step = 3;
    }

    // ================= Form 3 — Pemetaan MK =================

    public function toggleMatakuliah(int $matakuliahId): void
    {
        $mhs = $this->mhs();
        $existing = PengajuanMk::where('mahasiswa_id', $mhs->id)->where('matakuliah_id', $matakuliahId)->first();

        if ($existing) {
            $existing->delete();

            return;
        }

        $this->lengkapiMatakuliahId = $matakuliahId;
        $this->lengkapiBuktiIds = [];
        $this->lengkapiNilaiAngka = null;
        $this->lengkapiNilaiHuruf = 'A';
        $this->showLengkapiModal = true;
    }

    public function bukaLengkapi(int $matakuliahId): void
    {
        $pengajuan = PengajuanMk::where('mahasiswa_id', $this->mahasiswaId)->where('matakuliah_id', $matakuliahId)->first();

        $this->lengkapiMatakuliahId = $matakuliahId;
        $this->lengkapiBuktiIds = $pengajuan?->bukti->pluck('id')->toArray() ?? [];
        $this->lengkapiNilaiAngka = $pengajuan?->nilai_mandiri_angka;
        $this->lengkapiNilaiHuruf = $pengajuan?->nilai_mandiri_huruf ?? 'A';
        $this->showLengkapiModal = true;
    }

    public function simpanLengkapi(): void
    {
        $data = $this->validate([
            'lengkapiBuktiIds' => ['required', 'array', 'min:1'],
            'lengkapiNilaiAngka' => ['required', 'integer', 'min:0', 'max:100'],
            'lengkapiNilaiHuruf' => ['required', 'in:A,AB,B,BC,C,D,E'],
        ], [
            'lengkapiBuktiIds.required' => 'Pilih minimal 1 bukti untuk matakuliah ini.',
        ]);

        $pengajuan = PengajuanMk::updateOrCreate(
            ['mahasiswa_id' => $this->mahasiswaId, 'matakuliah_id' => $this->lengkapiMatakuliahId],
            [
                'nilai_mandiri_angka' => $data['lengkapiNilaiAngka'],
                'nilai_mandiri_huruf' => $data['lengkapiNilaiHuruf'],
            ]
        );

        $pengajuan->bukti()->sync($data['lengkapiBuktiIds']);

        // Keep self-assessment rows in sync with the matakuliah's current CPMK set.
        $matakuliah = $pengajuan->matakuliah()->with('cpmk')->first();
        foreach ($matakuliah->cpmk as $cpmk) {
            SelfAssessment::firstOrCreate([
                'pengajuan_mk_id' => $pengajuan->id,
                'cpmk_id' => $cpmk->id,
            ]);
        }

        $this->closeLengkapiModal();
    }

    public function closeLengkapiModal(): void
    {
        $this->showLengkapiModal = false;
        $this->lengkapiMatakuliahId = null;
        $this->lengkapiBuktiIds = [];
        $this->resetErrorBag();
    }

    public function lanjutKeForm4(): void
    {
        $belumLengkap = PengajuanMk::where('mahasiswa_id', $this->mahasiswaId)
            ->where(fn ($q) => $q->whereNull('nilai_mandiri_angka')->orWhereDoesntHave('bukti'))
            ->exists();

        if (PengajuanMk::where('mahasiswa_id', $this->mahasiswaId)->count() === 0) {
            session()->flash('error', 'Pilih minimal 1 matakuliah untuk direkognisi.');

            return;
        }

        if ($belumLengkap) {
            session()->flash('error', 'Masih ada matakuliah yang belum dilengkapi bukti/nilai mandiri.');

            return;
        }

        $this->step = 4;
    }

    // ================= Form 4 — CV & Pernyataan =================

    private function loadForm4(): void
    {
        $mhs = $this->mhs();
        $cv = $mhs->dokumenCv;
        $pernyataan = $mhs->pernyataan;

        if ($cv) {
            $this->cv_url = $cv->url;
            $this->cv_keterangan = (string) $cv->keterangan;
        }

        if ($pernyataan) {
            $this->pernyataan_setuju = $pernyataan->disetujui;
            $this->pernyataan_tempat = (string) $pernyataan->tempat;
            $this->pernyataan_tanggal = $pernyataan->tanggal?->format('Y-m-d');
        } else {
            $this->pernyataan_tempat = $this->mhs()->prodi->nama ?? '';
            $this->pernyataan_tanggal = now()->format('Y-m-d');
        }
    }

    public function simpanForm4(): void
    {
        $data = $this->validate([
            'cv_url' => ['required', 'url', 'max:500'],
            'cv_keterangan' => ['nullable', 'string'],
            'pernyataan_setuju' => ['accepted'],
            'pernyataan_tempat' => ['required', 'string', 'max:100'],
            'pernyataan_tanggal' => ['required', 'date'],
        ], [
            'pernyataan_setuju.accepted' => 'Anda harus menyetujui pernyataan calon peserta untuk melanjutkan.',
        ]);

        $mhs = $this->mhs();

        $mhs->dokumenCv()->updateOrCreate(['mahasiswa_id' => $mhs->id], [
            'url' => $data['cv_url'],
            'keterangan' => $data['cv_keterangan'],
        ]);

        $mhs->pernyataan()->updateOrCreate(['mahasiswa_id' => $mhs->id], [
            'disetujui' => true,
            'tempat' => $data['pernyataan_tempat'],
            'tanggal' => $data['pernyataan_tanggal'],
            'waktu_setuju' => now(),
            'ip_address' => request()->ip(),
        ]);

        $this->step = 5;
    }

    // ================= Form 5 — Self Assessment =================

    public function setSelfAssessment(int $selfAssessmentId, string $status): void
    {
        SelfAssessment::whereHas('pengajuanMk', fn ($q) => $q->where('mahasiswa_id', $this->mahasiswaId))
            ->where('id', $selfAssessmentId)
            ->update(['status' => $status]);
    }

    public function centangSemuaMampu(int $pengajuanMkId): void
    {
        SelfAssessment::where('pengajuan_mk_id', $pengajuanMkId)
            ->whereHas('pengajuanMk', fn ($q) => $q->where('mahasiswa_id', $this->mahasiswaId))
            ->update(['status' => 'mampu']);
    }

    public function centangSemuaMampuGlobal(): void
    {
        SelfAssessment::whereHas('pengajuanMk', fn ($q) => $q->where('mahasiswa_id', $this->mahasiswaId))
            ->update(['status' => 'mampu']);
    }

    public function lanjutKeForm6(): void
    {
        $belum = SelfAssessment::whereHas('pengajuanMk', fn ($q) => $q->where('mahasiswa_id', $this->mahasiswaId))
            ->whereNull('status')
            ->exists();

        if ($belum) {
            session()->flash('error', 'Seluruh CPMK harus dinilai (Mampu / Belum Mampu) sebelum lanjut.');

            return;
        }

        $this->step = 6;
    }

    // ================= Form 6 — MK Asal =================

    public function bukaMkAsal(int $pengajuanMkId): void
    {
        $this->mkAsalPengajuanMkId = $pengajuanMkId;
        $this->resetMkAsalForm();
        $this->showMkAsalModal = true;
    }

    public function tambahMkAsalRow(): void
    {
        $data = $this->validate([
            'mkAsalKode' => ['required', 'string', 'max:30'],
            'mkAsalNama' => ['required', 'string', 'max:150'],
            'mkAsalCpmk' => ['required', 'string'],
        ]);

        if ($this->editingMkAsalId) {
            MkAsal::where('id', $this->editingMkAsalId)->update([
                'kode_asal' => $data['mkAsalKode'],
                'nama_mk_asal' => $data['mkAsalNama'],
                'cpmk_asal' => $data['mkAsalCpmk'],
            ]);
        } else {
            MkAsal::create([
                'pengajuan_mk_id' => $this->mkAsalPengajuanMkId,
                'kode_asal' => $data['mkAsalKode'],
                'nama_mk_asal' => $data['mkAsalNama'],
                'cpmk_asal' => $data['mkAsalCpmk'],
            ]);
        }

        $this->resetMkAsalForm();
    }

    public function editMkAsalRow(int $id): void
    {
        $row = MkAsal::findOrFail($id);
        $this->editingMkAsalId = $row->id;
        $this->mkAsalKode = $row->kode_asal;
        $this->mkAsalNama = $row->nama_mk_asal;
        $this->mkAsalCpmk = $row->cpmk_asal;
    }

    public function hapusMkAsalRow(int $id): void
    {
        MkAsal::where('id', $id)->delete();
    }

    public function batalEditMkAsal(): void
    {
        $this->resetMkAsalForm();
    }

    public function closeMkAsalModal(): void
    {
        $this->showMkAsalModal = false;
        $this->mkAsalPengajuanMkId = null;
        $this->resetMkAsalForm();
    }

    private function resetMkAsalForm(): void
    {
        $this->reset(['editingMkAsalId', 'mkAsalKode', 'mkAsalNama', 'mkAsalCpmk']);
        $this->resetErrorBag();
    }

    public function pengajuanBerbuktiFormal(): Collection
    {
        return PengajuanMk::where('mahasiswa_id', $this->mahasiswaId)
            ->with(['matakuliah', 'bukti', 'mkAsal'])
            ->get()
            ->filter(fn ($p) => $p->bukti->contains('kategori', 'formal'));
    }

    public function lanjutKeRingkasan(): void
    {
        $belum = $this->pengajuanBerbuktiFormal()->filter(fn ($p) => $p->mkAsal->isEmpty());

        if ($belum->isNotEmpty()) {
            session()->flash('error', 'Lengkapi data matakuliah asal untuk setiap matakuliah berbukti formal.');

            return;
        }

        $this->step = 7;
    }

    public function kembali(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    public function keStep(int $step): void
    {
        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    // ================= Submit Final =================

    public function submitFinal(): void
    {
        $mhs = $this->mhs();

        if (! in_array($mhs->status_berkas, ['draft', 'dikembalikan'])) {
            return;
        }

        $mhs->update(['status_berkas' => 'diajukan']);
        session()->flash('success', 'Berkas berhasil diajukan dan sekarang terkunci. Anda akan diberi tahu setelah asesor mulai menilai.');
        $this->step = 7;
    }

    public function render()
    {
        $mhs = $this->mhs();

        $matakuliahOptions = $mhs->skema?->matakuliah()->where('is_aktif', true)->where('dapat_direkognisi', true)->with('cpmk')->orderBy('nama')->get()
            ?? collect();

        $buktiList = Bukti::where('mahasiswa_id', $this->mahasiswaId)->orderByDesc('created_at')->get();

        $pengajuanList = PengajuanMk::where('mahasiswa_id', $this->mahasiswaId)
            ->with(['matakuliah', 'bukti', 'selfAssessment.cpmk', 'mkAsal'])
            ->get()
            ->keyBy('matakuliah_id');

        $lengkapiBuktiOptions = $this->lengkapiMatakuliahId
            ? $buktiList
            : collect();

        $pengembalianTerakhir = $mhs->status_berkas === 'dikembalikan'
            ? $mhs->pengembalianBerkas()->with('admin')->first()
            : null;

        return view('livewire.mahasiswa.wizard', [
            'mhs' => $mhs,
            'matakuliahOptions' => $matakuliahOptions,
            'buktiList' => $buktiList,
            'pengajuanList' => $pengajuanList,
            'lengkapiBuktiOptions' => $lengkapiBuktiOptions,
            'pengajuanBerbuktiFormal' => $this->step >= 6 ? $this->pengajuanBerbuktiFormal() : collect(),
            'readOnly' => $this->isReadOnly(),
            'pengembalianTerakhir' => $pengembalianTerakhir,
        ]);
    }
}
