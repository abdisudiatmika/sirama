<?php

namespace Tests\Feature;

use App\Livewire\AdminProdi\SkemaManager;
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
use App\Services\RekapitulasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
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
            $skema->matakuliah()->attach($mk->id, ['direkognisi' => true]);
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

    /** Fills the bulk template: data rows start at 6, assessor columns F–H, NIM in B. */
    private function fileMassal(array $nilai, array $tambahan = []): string
    {
        $service = app(NilaiAdminService::class);
        $book = $service->templateMassal($service->mahasiswaMassal($this->mahasiswa->prodi_id), 'uji');
        $sheet = $book->getActiveSheet();
        foreach ($nilai as $baris => $kolom) {
            foreach ($kolom as $huruf => $v) {
                $sheet->setCellValue($huruf.(6 + $baris), $v);
            }
        }
        foreach ($tambahan as $i => [$nim, $kode]) {
            $sheet->setCellValueExplicit('B'.(20 + $i), $nim, DataType::TYPE_STRING);
            $sheet->setCellValue('D'.(20 + $i), $kode);
            $sheet->setCellValue('F'.(20 + $i), 80);
        }
        $path = tempnam(sys_get_temp_dir(), 'massal').'.xlsx';
        (new Xlsx($book))->save($path);

        return $path;
    }

    public function test_impor_massal_diperiksa_dulu_lalu_hanya_nilai_berubah_disimpan(): void
    {
        $service = app(NilaiAdminService::class);
        $prodiId = $this->mahasiswa->prodi_id;

        $cek = $service->periksaMassal($prodiId, $this->fileMassal([0 => ['F' => 80, 'G' => 82, 'H' => 84], 1 => ['F' => 70, 'G' => 71, 'H' => 72]]));
        $this->assertSame([], $cek['galat']);
        $this->assertSame(6, $cek['ringkasan']['berubah']);
        $this->assertSame(0, Penilaian::count(), 'pratinjau tidak boleh menyimpan');

        $this->assertSame(6, $service->simpanRencana($cek['rencana'], $this->admin->id));
        $this->assertSame('selesai_dinilai', $this->mahasiswa->fresh()->status_berkas);

        // The re-downloaded template carries the same scores: nothing to change.
        $ulang = $service->periksaMassal($prodiId, $this->fileMassal([]));
        $this->assertSame(6, $ulang['ringkasan']['nilai']);
        $this->assertSame(0, $ulang['ringkasan']['berubah']);
    }

    public function test_impor_massal_menolak_nim_dan_kode_asing(): void
    {
        $cek = app(NilaiAdminService::class)->periksaMassal($this->mahasiswa->prodi_id, $this->fileMassal([0 => ['F' => 80]], [['9999999999', 'MKB001'], ['2026010001', 'MKX999']]));

        $this->assertCount(2, $cek['galat']);
        $this->assertStringContainsString('NIM 9999999999', $cek['galat'][0]);
        $this->assertStringContainsString('MKX999', $cek['galat'][1]);
    }

    public function test_template_mahasiswa_lain_ditolak(): void
    {
        $hasil = app(NilaiAdminService::class)->impor($this->mahasiswa, $this->fileTerisi([0 => ['E' => 80]], '9999999999'), $this->admin->id);

        $this->assertStringContainsString('9999999999', $hasil['galat'][0]);
        $this->assertSame(0, Penilaian::count());
    }

    public function test_mk_wajib_kuliah_tidak_dinilai_dan_selalu_tidak_direkognisi(): void
    {
        $skema = $this->mahasiswa->skema;
        $wajib = Matakuliah::create(['prodi_id' => $this->mahasiswa->prodi_id, 'kode' => 'MKW001', 'nama' => 'Akuntansi Internasional', 'sks' => 3, 'skor_minimum' => 66, 'dapat_direkognisi' => true, 'is_aktif' => true]);
        $skema->matakuliah()->attach($wajib->id, ['direkognisi' => false]);
        PengajuanMk::create(['mahasiswa_id' => $this->mahasiswa->id, 'matakuliah_id' => $wajib->id, 'nilai_mandiri_angka' => 80, 'nilai_mandiri_huruf' => 'AB']);
        $mhs = Mahasiswa::find($this->mahasiswa->id);

        $this->assertFalse($mhs->mkDirekognisi($wajib->id));
        $this->assertCount(2, $mhs->pengajuanDirekognisi());
        $this->assertSame(2, $skema->matakuliahDirekognisi()->count());

        // The template lists only the two recognisable courses.
        $sheet = app(NilaiAdminService::class)->template($mhs)->getActiveSheet();
        $this->assertSame('MKB002', $sheet->getCell('B9')->getValue());
        $this->assertEmpty($sheet->getCell('B10')->getValue());

        // The recap still lists the wajib course, always as not recognised.
        $baris = app(RekapitulasiService::class)->untuk($mhs)['baris'];
        $this->assertCount(3, $baris);
        $rowWajib = $baris->first(fn ($r) => $r->matakuliah->id === $wajib->id);
        $this->assertSame('Tidak Direkognisi', $rowWajib->status);
        $this->assertNull($rowWajib->pengajuan);

        // Finishing the two recognisable courses completes the file.
        $hasil = app(NilaiAdminService::class)->impor($mhs, $this->fileTerisi([0 => ['E' => 80, 'F' => 80, 'G' => 80], 1 => ['E' => 80, 'F' => 80, 'G' => 80]]), $this->admin->id);
        $this->assertSame([], $hasil['galat']);
        $this->assertSame('selesai_dinilai', $mhs->fresh()->status_berkas);
    }

    public function test_menu_skema_menyimpan_tanda_wajib_kuliah(): void
    {
        $skema = $this->mahasiswa->skema;
        $this->admin->prodiDikelola()->attach($this->mahasiswa->prodi_id);
        [$satu, $dua] = $skema->matakuliah()->orderBy('kode')->pluck('matakuliah.id')->all();

        Livewire::actingAs($this->admin)
            ->test(SkemaManager::class)
            ->call('edit', $skema->id)
            ->assertSet('mkWajib', [])
            ->set('mkWajib', [(string) $dua])
            ->call('simpan')
            ->assertHasNoErrors()
            ->assertSee('1 direkognisi · 1 wajib');

        $this->assertSame([$satu], $skema->fresh()->matakuliahDirekognisi()->pluck('matakuliah.id')->all());
    }
}
