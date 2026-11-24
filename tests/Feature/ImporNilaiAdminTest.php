<?php

namespace Tests\Feature;

use App\Models\Asesor;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\PengajuanMk;
use App\Models\Penilaian;
use App\Models\PenugasanAsesor;
use App\Models\Prodi;
use App\Models\Skema;
use App\Models\User;
use App\Services\NilaiAdminService;
use App\Services\PenilaianService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class ImporNilaiAdminTest extends TestCase
{
    use RefreshDatabase;

    private Mahasiswa $mahasiswa;

    /** @var array<int,Asesor> keyed by urutan */
    private array $asesor = [];

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $prodi = Prodi::create(['kode' => 'AKM', 'nama' => 'Akuntansi Manajerial', 'jenjang' => 'D4', 'is_aktif' => true]);
        $skema = Skema::create(['prodi_id' => $prodi->id, 'nama' => 'Reguler', 'jenis' => 'RPL', 'tahun_akademik' => '2026/2027', 'is_aktif' => true]);
        $this->admin = User::factory()->create();
        $this->mahasiswa = Mahasiswa::create([
            'user_id' => User::factory()->create()->id, 'prodi_id' => $prodi->id, 'skema_id' => $skema->id,
            'nim' => '2026010001', 'tahun_masuk' => 2026, 'status_berkas' => 'diajukan',
        ]);

        foreach ([1, 2, 3] as $urutan) {
            $this->asesor[$urutan] = Asesor::create(['user_id' => User::factory()->create()->id, 'nidn' => "00{$urutan}", 'is_aktif' => true]);
            PenugasanAsesor::create(['mahasiswa_id' => $this->mahasiswa->id, 'asesor_id' => $this->asesor[$urutan]->id, 'urutan' => $urutan, 'assigned_at' => now()]);
        }

        foreach (['MKB001' => 'Akuntansi Biaya', 'MKB002' => 'Perpajakan'] as $kode => $nama) {
            $mk = Matakuliah::create(['prodi_id' => $prodi->id, 'kode' => $kode, 'nama' => $nama, 'sks' => 3, 'skor_minimum' => 66, 'dapat_direkognisi' => true, 'is_aktif' => true]);
            $p = PengajuanMk::create(['mahasiswa_id' => $this->mahasiswa->id, 'matakuliah_id' => $mk->id, 'nilai_mandiri_angka' => 80, 'nilai_mandiri_huruf' => 'AB']);
            $bukti = $this->mahasiswa->bukti()->create(['kategori' => 'informal', 'jenis_bukti' => 'Sertifikat', 'judul' => 'Pelatihan', 'url' => 'https://contoh.test/'.$kode]);
            $p->bukti()->attach($bukti->id);
        }
    }

    /** Fills the downloaded template: rows start at 8, assessor columns E–G. */
    private function fileTerisi(array $nilai, ?string $nim = null): string
    {
        $book = app(NilaiAdminService::class)->template($this->mahasiswa);
        $sheet = $book->getActiveSheet();
        if ($nim) {
            $sheet->setCellValue('B2', $nim);
        }
        foreach ($nilai as $baris => $kolom) {
            foreach ($kolom as $huruf => $v) {
                $sheet->setCellValue($huruf.(8 + $baris), $v);
            }
        }
        $path = tempnam(sys_get_temp_dir(), 'nilai').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    public function test_impor_menyimpan_nilai_tiap_asesor_sebagai_nilai_akhir(): void
    {
        $path = $this->fileTerisi([0 => ['E' => 80, 'F' => 85, 'G' => '78,5'], 1 => ['E' => 70]]);

        $hasil = app(NilaiAdminService::class)->impor($this->mahasiswa, $path, $this->admin->id);

        $this->assertSame([], $hasil['galat']);
        $this->assertSame(4, $hasil['tersimpan']);

        $akuntansiBiaya = PengajuanMk::whereHas('matakuliah', fn ($q) => $q->where('kode', 'MKB001'))->first();
        $this->assertSame(81.17, app(PenilaianService::class)->rataRataAsesmen($akuntansiBiaya)['rata_rata']);
        $this->assertSame(4, Penilaian::where('diinput_oleh', $this->admin->id)->count());
        $this->assertSame('sedang_dinilai', $this->mahasiswa->fresh()->status_berkas);
    }

    public function test_sel_kosong_tidak_mengubah_dan_semua_lengkap_menjadi_selesai(): void
    {
        $service = app(NilaiAdminService::class);
        $service->impor($this->mahasiswa, $this->fileTerisi([0 => ['E' => 80, 'F' => 80, 'G' => 80], 1 => ['E' => 75, 'F' => 75, 'G' => 75]]), $this->admin->id);

        // The template now carries those scores; change one cell and clear another.
        $book = $service->template($this->mahasiswa->fresh());
        $this->assertSame(80.0, (float) $book->getActiveSheet()->getCell('E8')->getValue());

        $hasil = $service->impor($this->mahasiswa->fresh(), $this->fileTerisi([0 => ['E' => 90, 'F' => null]]), $this->admin->id);

        $this->assertSame([], $hasil['galat']);
        $pertama = PengajuanMk::whereHas('matakuliah', fn ($q) => $q->where('kode', 'MKB001'))->first();
        $this->assertEquals(90, Penilaian::where('pengajuan_mk_id', $pertama->id)->where('asesor_id', $this->asesor[1]->id)->value('nilai_akhir'));
        $this->assertEquals(80, Penilaian::where('pengajuan_mk_id', $pertama->id)->where('asesor_id', $this->asesor[2]->id)->value('nilai_akhir'));
        $this->assertSame('selesai_dinilai', $this->mahasiswa->fresh()->status_berkas);
    }

    public function test_satu_baris_salah_membatalkan_seluruh_impor(): void
    {
        $hasil = app(NilaiAdminService::class)->impor($this->mahasiswa, $this->fileTerisi([0 => ['E' => 80], 1 => ['F' => 120]]), $this->admin->id);

        $this->assertCount(1, $hasil['galat']);
        $this->assertStringContainsString('0–100', $hasil['galat'][0]);
        $this->assertSame(0, Penilaian::count());
    }

    public function test_template_mahasiswa_lain_ditolak(): void
    {
        $hasil = app(NilaiAdminService::class)->impor($this->mahasiswa, $this->fileTerisi([0 => ['E' => 80]], '9999999999'), $this->admin->id);

        $this->assertStringContainsString('9999999999', $hasil['galat'][0]);
        $this->assertSame(0, Penilaian::count());
    }
}
