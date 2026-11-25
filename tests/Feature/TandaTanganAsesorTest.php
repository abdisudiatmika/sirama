<?php

namespace Tests\Feature;

use App\Livewire\AdminProdi\AsesorDirectory;
use App\Livewire\Asesor\Dashboard;
use App\Models\Asesor;
use App\Models\Mahasiswa;
use App\Models\PenugasanAsesor;
use App\Models\Prodi;
use App\Models\Skema;
use App\Models\User;
use App\Services\Dokumen\DokumenMahasiswaService;
use App\Services\TandaTanganAsesorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TandaTanganAsesorTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Asesor $asesor;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        foreach (['admin_prodi', 'asesor'] as $r) {
            Role::findOrCreate($r);
        }

        $prodi = Prodi::create(['kode' => 'AKM', 'nama' => 'Akuntansi Manajerial', 'jenjang' => 'D4', 'is_aktif' => true]);
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin_prodi');
        $this->admin->prodiDikelola()->attach($prodi->id);

        $user = User::factory()->create(['name' => 'Asesor Satu']);
        $user->assignRole('asesor');
        $this->asesor = Asesor::create(['user_id' => $user->id, 'nidn' => '0011', 'no_hp' => '0812', 'is_aktif' => true]);
    }

    public function test_admin_mengunggah_mengganti_dan_menghapus_ttd(): void
    {
        $uji = Livewire::actingAs($this->admin)->test(AsesorDirectory::class);

        $uji->call('bukaTtd', $this->asesor->id)
            ->set('fileTtd', UploadedFile::fake()->create('ttd.pdf', 10, 'application/pdf'))
            ->call('simpanTtd')
            ->assertHasErrors('fileTtd');

        $uji->set('fileTtd', UploadedFile::fake()->image('ttd.png', 300, 100))->call('simpanTtd')->assertHasNoErrors();
        $pertama = $this->asesor->fresh()->tanda_tangan;
        Storage::disk('local')->assertExists($pertama);

        $uji->call('bukaTtd', $this->asesor->id)->set('fileTtd', UploadedFile::fake()->image('baru.jpg', 300, 100))->call('simpanTtd');
        $kedua = $this->asesor->fresh()->tanda_tangan;
        $this->assertNotSame($pertama, $kedua);
        Storage::disk('local')->assertMissing($pertama);

        $this->actingAs($this->admin)->get(route('admin-prodi.asesor.ttd', $this->asesor))->assertOk();

        $uji->call('bukaTtd', $this->asesor->id)->call('hapusTtd');
        $this->assertNull($this->asesor->fresh()->tanda_tangan);
        Storage::disk('local')->assertMissing($kedua);
    }

    public function test_asesor_mengunggah_ttd_sendiri_dan_hanya_melihat_miliknya(): void
    {
        Livewire::actingAs($this->asesor->user)->test(Dashboard::class)
            ->set('fileTtd', UploadedFile::fake()->image('ttd.png', 300, 100))
            ->call('simpanTtd')
            ->assertHasNoErrors();

        $this->assertNotNull($this->asesor->fresh()->tanda_tangan);
        $this->actingAs($this->asesor->user)->get(route('asesor.ttd'))->assertOk();
        $this->actingAs($this->asesor->user)->get(route('admin-prodi.asesor.ttd', $this->asesor))->assertForbidden();
    }

    public function test_ekspor_excel_memuat_data_dan_gambar_ttd(): void
    {
        app(TandaTanganAsesorService::class)->simpan($this->asesor, UploadedFile::fake()->image('ttd.png', 300, 100));
        $lain = Asesor::create(['user_id' => User::factory()->create(['name' => 'Asesor Dua'])->id, 'is_aktif' => true]);

        $respon = $this->actingAs($this->admin)->get(route('admin-prodi.asesor.ekspor'))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'ases').'.xlsx';
        file_put_contents($path, $respon->streamedContent());

        $sheet = IOFactory::load($path)->getActiveSheet();
        $this->assertSame('Asesor Dua', $sheet->getCell('B5')->getValue());
        $this->assertSame('Belum ada', $sheet->getCell('I5')->getValue());
        $this->assertSame('Asesor Satu', $sheet->getCell('B6')->getValue());
        $this->assertSame('0011', $sheet->getCell('E6')->getValue());
        $this->assertSame('Ada', $sheet->getCell('I6')->getValue());
        $this->assertCount(1, $sheet->getDrawingCollection());
        $this->assertSame('J6', $sheet->getDrawingCollection()[0]->getCoordinates());
    }

    public function test_form5_mencetak_ttd_asesor(): void
    {
        $prodi = Prodi::first();
        $skema = Skema::create(['prodi_id' => $prodi->id, 'nama' => 'Reguler', 'jenis' => 'RPL', 'tahun_akademik' => '2026/2027', 'is_aktif' => true]);
        $mhs = Mahasiswa::create(['user_id' => User::factory()->create()->id, 'prodi_id' => $prodi->id, 'skema_id' => $skema->id, 'nim' => '2026010001', 'tahun_masuk' => 2026, 'status_berkas' => 'diajukan']);
        PenugasanAsesor::create(['mahasiswa_id' => $mhs->id, 'asesor_id' => $this->asesor->id, 'urutan' => 1, 'assigned_at' => now()]);

        $dokumen = app(DokumenMahasiswaService::class);
        $form5 = $dokumen->generator(5)->denganVarian(1);
        $tanpa = $dokumen->simpanFormulir($form5, $mhs);
        $this->assertStringNotContainsString('word/media/', $this->isiZip($tanpa));

        app(TandaTanganAsesorService::class)->simpan($this->asesor, UploadedFile::fake()->image('ttd.png', 300, 100));
        $dengan = $dokumen->simpanFormulir($form5, $mhs->fresh());
        $this->assertStringContainsString('word/media/', $this->isiZip($dengan));
    }

    public function test_admin_mengunduh_ttd_satu_per_satu_dan_zip(): void
    {
        $this->actingAs($this->admin)->get(route('admin-prodi.asesor.ttd-semua'))->assertNotFound();

        app(TandaTanganAsesorService::class)->simpan($this->asesor, UploadedFile::fake()->image('ttd.png', 300, 100));
        $user = User::factory()->create(['name' => 'Dr. Asesor Dua, S.E.']);
        $dua = Asesor::create(['user_id' => $user->id, 'is_aktif' => true]);
        app(TandaTanganAsesorService::class)->simpan($dua, UploadedFile::fake()->image('ttd.jpg', 300, 100));

        $this->actingAs($this->admin)->get(route('admin-prodi.asesor.ttd-unduh', $dua))
            ->assertOk()->assertDownload('TTD-dr-asesor-dua.jpg');

        $respon = $this->actingAs($this->admin)->get(route('admin-prodi.asesor.ttd-semua'))->assertOk();
        $this->assertSame(['TTD-asesor-satu.png', 'TTD-dr-asesor-dua.jpg'], explode("\n", $this->isiZip($respon->baseResponse->getFile()->getPathname())));

        $this->actingAs($this->asesor->user)->get(route('admin-prodi.asesor.ttd-semua'))->assertForbidden();
    }

    private function isiZip(string $path): string
    {
        $zip = new \ZipArchive;
        $zip->open($path);
        $nama = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $nama[] = $zip->getNameIndex($i);
        }
        $zip->close();

        return implode("\n", $nama);
    }
}
