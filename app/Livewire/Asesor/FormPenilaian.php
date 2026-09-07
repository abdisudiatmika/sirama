<?php

namespace App\Livewire\Asesor;

use App\Models\Asesor;
use App\Models\Mahasiswa;
use App\Models\PengajuanMk;
use App\Models\Penilaian;
use App\Models\ValidasiCpmk;
use App\Services\PenilaianService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Form Penilaian')]
class FormPenilaian extends Component
{
    public int $mahasiswaId;

    public int $pengajuanMkId;

    public int $asesorId;

    /** @var array<int,array{valid:bool,asli:bool,terkini:bool,memadai:bool}> keyed by cpmk_id */
    public array $vatm = [];

    public ?int $nilaiFormal = null;

    public string $catatanFormal = '';

    public string $kesenjanganFormal = '';

    public ?int $nilaiInformal = null;

    public string $catatanInformal = '';

    public string $kesenjanganInformal = '';

    public function mount(Mahasiswa $mahasiswa, PengajuanMk $pengajuanMk): void
    {
        $asesor = Auth::user()->asesor;
        abort_unless($asesor && $mahasiswa->penugasanAsesor()->where('asesor_id', $asesor->id)->exists(), 403);
        abort_unless($pengajuanMk->mahasiswa_id === $mahasiswa->id, 404);

        $this->mahasiswaId = $mahasiswa->id;
        $this->pengajuanMkId = $pengajuanMk->id;
        $this->asesorId = $asesor->id;

        $this->loadVatm();
        $this->loadPenilaian();
    }

    private function pengajuan(): PengajuanMk
    {
        return PengajuanMk::with(['matakuliah.cpmk', 'bukti', 'selfAssessment.cpmk', 'mkAsal'])->findOrFail($this->pengajuanMkId);
    }

    private function loadVatm(): void
    {
        $pengajuan = $this->pengajuan();
        $existing = ValidasiCpmk::where('pengajuan_mk_id', $this->pengajuanMkId)
            ->where('asesor_id', $this->asesorId)
            ->get()
            ->keyBy('cpmk_id');

        foreach ($pengajuan->matakuliah->cpmk as $cpmk) {
            $row = $existing->get($cpmk->id);
            $this->vatm[$cpmk->id] = [
                'valid' => (bool) $row?->valid,
                'asli' => (bool) $row?->asli,
                'terkini' => (bool) $row?->terkini,
                'memadai' => (bool) $row?->memadai,
            ];
        }
    }

    private function loadPenilaian(): void
    {
        $rows = Penilaian::where('pengajuan_mk_id', $this->pengajuanMkId)
            ->where('asesor_id', $this->asesorId)
            ->get()
            ->keyBy('jenis');

        if ($formal = $rows->get('formal')) {
            $this->nilaiFormal = $formal->nilai;
            $this->catatanFormal = (string) $formal->catatan_reviewer;
            $this->kesenjanganFormal = (string) $formal->catatan_kesenjangan;
        }

        if ($informal = $rows->get('informal')) {
            $this->nilaiInformal = $informal->nilai;
            $this->catatanInformal = (string) $informal->catatan_reviewer;
            $this->kesenjanganInformal = (string) $informal->catatan_kesenjangan;
        }
    }

    public function toggleVatm(int $cpmkId, string $kriteria): void
    {
        $this->vatm[$cpmkId][$kriteria] = ! $this->vatm[$cpmkId][$kriteria];
        $this->simpanVatm($cpmkId);
    }

    public function centangSemuaVatm(int $cpmkId): void
    {
        $this->vatm[$cpmkId] = ['valid' => true, 'asli' => true, 'terkini' => true, 'memadai' => true];
        $this->simpanVatm($cpmkId);
    }

    public function centangSemuaVatmGlobal(): void
    {
        foreach ($this->vatm as $cpmkId => $row) {
            $this->vatm[$cpmkId] = ['valid' => true, 'asli' => true, 'terkini' => true, 'memadai' => true];
            $this->simpanVatm($cpmkId);
        }
    }

    private function simpanVatm(int $cpmkId): void
    {
        ValidasiCpmk::updateOrCreate(
            ['pengajuan_mk_id' => $this->pengajuanMkId, 'asesor_id' => $this->asesorId, 'cpmk_id' => $cpmkId],
            $this->vatm[$cpmkId]
        );
    }

    public function simpanFormal(PenilaianService $service): void
    {
        $this->validate([
            'nilaiFormal' => ['required', 'integer', 'min:0', 'max:100'],
            'catatanFormal' => ['nullable', 'string'],
            'kesenjanganFormal' => ['nullable', 'string'],
        ]);

        $service->simpanPenilaian(
            $this->pengajuan(),
            Asesor::findOrFail($this->asesorId),
            'formal',
            $this->nilaiFormal,
            $this->catatanFormal,
            $this->kesenjanganFormal,
        );

        session()->flash('success', 'Penilaian formal tersimpan.');
        $this->updateStatusBerkas();
    }

    public function simpanInformal(PenilaianService $service): void
    {
        $this->validate([
            'nilaiInformal' => ['required', 'integer', 'min:0', 'max:100'],
            'catatanInformal' => ['nullable', 'string'],
            'kesenjanganInformal' => ['nullable', 'string'],
        ]);

        $service->simpanPenilaian(
            $this->pengajuan(),
            Asesor::findOrFail($this->asesorId),
            'informal',
            $this->nilaiInformal,
            $this->catatanInformal,
            $this->kesenjanganInformal,
        );

        session()->flash('success', 'Penilaian informal tersimpan.');
        $this->updateStatusBerkas();
    }

    /**
     * First save by any asesor flips the file to "sedang_dinilai"; once every
     * assigned asesor has finished every matakuliah it flips to
     * "selesai_dinilai" (§9).
     */
    private function updateStatusBerkas(): void
    {
        $mahasiswa = Mahasiswa::with(['pengajuanMk', 'penugasanAsesor'])->findOrFail($this->mahasiswaId);

        if ($mahasiswa->status_berkas === 'diajukan') {
            $mahasiswa->update(['status_berkas' => 'sedang_dinilai']);
        }

        $asesorIds = $mahasiswa->penugasanAsesor->pluck('asesor_id');
        $semuaSelesai = $asesorIds->isNotEmpty() && $asesorIds->every(
            fn ($asesorId) => $mahasiswa->pengajuanMk->every(fn ($p) => $p->fresh(['bukti', 'penilaian'])->selesaiDinilaiOleh($asesorId))
        );

        if ($semuaSelesai && $mahasiswa->status_berkas !== 'selesai_dinilai') {
            $mahasiswa->update(['status_berkas' => 'selesai_dinilai']);
        }
    }

    public function simpanDanLanjut(PenilaianService $service): void
    {
        $pengajuan = $this->pengajuan();
        $jenis = $pengajuan->jenisPenilaianTersedia();

        if (in_array('formal', $jenis)) {
            $this->simpanFormal($service);
        }

        if (in_array('informal', $jenis)) {
            $this->simpanInformal($service);
        }

        $mahasiswa = Mahasiswa::with('pengajuanMk')->findOrFail($this->mahasiswaId);
        $urutan = $mahasiswa->pengajuanMk->pluck('id')->values();
        $posisi = $urutan->search($this->pengajuanMkId);
        $nextId = $urutan->get($posisi + 1);

        if ($nextId) {
            $this->redirect(route('asesor.penilaian', [$this->mahasiswaId, $nextId]), navigate: true);
        } else {
            $this->redirect(route('asesor.mahasiswa', $this->mahasiswaId), navigate: true);
        }
    }

    public function render()
    {
        $pengajuan = $this->pengajuan();
        $mahasiswa = Mahasiswa::with('pengajuanMk')->findOrFail($this->mahasiswaId);

        return view('livewire.asesor.form-penilaian', [
            'mahasiswa' => $mahasiswa,
            'pengajuan' => $pengajuan,
            'jenisTersedia' => $pengajuan->jenisPenilaianTersedia(),
        ]);
    }
}
