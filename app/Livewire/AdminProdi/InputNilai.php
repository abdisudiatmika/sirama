<?php

namespace App\Livewire\AdminProdi;

use App\Livewire\Concerns\InteractsWithProdiContext;
use App\Models\Mahasiswa;
use App\Models\PengajuanMk;
use App\Models\Skema;
use App\Services\NilaiAdminService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/**
 * Bulk entry of assessor scores: download one Excel for many students, fill
 * it, upload it, review what would change, then save. Per-student entry stays
 * in the student panel; both use NilaiAdminService and the same rules.
 */
#[Layout('layouts.app')]
#[Title('Input Nilai')]
class InputNilai extends Component
{
    use InteractsWithProdiContext, WithFileUploads;

    public ?int $filterSkemaId = null;

    public ?int $filterAngkatan = null;

    public string $filterStatus = '';

    /** @var TemporaryUploadedFile|null */
    public $file = null;

    /** @var array{galat: list<string>, ringkasan: array{nilai:int, berubah:int, mahasiswa: list<array{nim:string, nama:string, berubah:int}>}}|null */
    public ?array $pratinjau = null;

    public ?string $pesan = null;

    public function updatedFile(): void
    {
        $this->reset('pratinjau', 'pesan');
    }

    public function periksa(NilaiAdminService $nilai): void
    {
        $this->validateFile();
        $hasil = $nilai->periksaMassal($this->prodiId, $this->file->getRealPath());
        $this->pratinjau = ['galat' => $hasil['galat'], 'ringkasan' => $hasil['ringkasan']];
        $this->pesan = null;
    }

    /** Checks the same uploaded file again and saves it only when it is still free of errors. */
    public function simpan(NilaiAdminService $nilai): void
    {
        $this->validateFile();
        $hasil = $nilai->periksaMassal($this->prodiId, $this->file->getRealPath());

        if ($hasil['galat']) {
            $this->pratinjau = ['galat' => $hasil['galat'], 'ringkasan' => $hasil['ringkasan']];

            return;
        }

        $berubah = $nilai->simpanRencana($hasil['rencana'], Auth::id());
        $jumlahMahasiswa = count($hasil['ringkasan']['mahasiswa']);
        $this->pesan = "{$berubah} nilai disimpan untuk {$jumlahMahasiswa} mahasiswa. Status berkas sudah diperbarui.";
        $this->reset('file', 'pratinjau');
    }

    public function batal(): void
    {
        $this->reset('file', 'pratinjau', 'pesan');
    }

    private function validateFile(): void
    {
        $this->validate(
            ['file' => ['required', 'file', 'mimes:xlsx', 'max:10240']],
            ['file.required' => 'Pilih file Excel nilai terlebih dahulu.', 'file.mimes' => 'Gunakan file .xlsx dari template nilai massal.'],
        );
    }

    public function render()
    {
        $query = Mahasiswa::where('prodi_id', $this->prodiId)->whereHas('pengajuanMk')
            ->when($this->filterSkemaId, fn ($q) => $q->where('skema_id', $this->filterSkemaId))
            ->when($this->filterAngkatan, fn ($q) => $q->where('tahun_masuk', $this->filterAngkatan))
            ->when($this->filterStatus, fn ($q) => $q->where('status_berkas', $this->filterStatus));

        return view('livewire.admin-prodi.input-nilai', [
            'prodiOptions' => $this->prodiOptions(),
            'skemaOptions' => Skema::where('prodi_id', $this->prodiId)->orderBy('nama')->get(),
            'angkatanOptions' => Mahasiswa::where('prodi_id', $this->prodiId)->distinct()->orderByDesc('tahun_masuk')->pluck('tahun_masuk'),
            'jumlahMahasiswa' => (clone $query)->count(),
            'jumlahBaris' => PengajuanMk::whereIn('mahasiswa_id', (clone $query)->select('id'))
                ->whereExists(fn ($q) => $q->selectRaw(1)->from('skema_matakuliah')->join('mahasiswa', 'mahasiswa.skema_id', '=', 'skema_matakuliah.skema_id')
                    ->whereColumn('mahasiswa.id', 'pengajuan_mk.mahasiswa_id')->whereColumn('skema_matakuliah.matakuliah_id', 'pengajuan_mk.matakuliah_id')
                    ->where('skema_matakuliah.direkognisi', true))
                ->count(),
            'urlTemplate' => route('admin-prodi.input-nilai.template', array_filter([
                'prodi' => $this->prodiId,
                'skema' => $this->filterSkemaId,
                'angkatan' => $this->filterAngkatan,
                'status' => $this->filterStatus ?: null,
            ])),
        ]);
    }
}
