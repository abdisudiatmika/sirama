<?php

namespace Tests\Feature;

use App\Livewire\Mahasiswa\Wizard;
use App\Models\Cpmk;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\PengajuanMk;
use App\Models\Prodi;
use App\Models\SelfAssessment;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class WizardMkWajibTest extends TestCase
{
    use RefreshDatabase;

    public function test_mahasiswa_tidak_melihat_dan_tidak_wajib_melengkapi_mk_wajib_kuliah(): void
    {
        $prodi = Prodi::create(['kode' => 'AKM', 'nama' => 'Akuntansi Manajerial', 'jenjang' => 'D4', 'is_aktif' => true]);
        $skema = Skema::create(['prodi_id' => $prodi->id, 'nama' => 'Skema Koperasi', 'jenis' => 'RPL', 'tahun_akademik' => '2026/2027', 'is_aktif' => true]);
        $user = User::factory()->create();
        $mhs = Mahasiswa::create(['user_id' => $user->id, 'prodi_id' => $prodi->id, 'skema_id' => $skema->id, 'nim' => '2026010001', 'tahun_masuk' => 2026, 'status_berkas' => 'dikembalikan']);

        $buat = function (string $kode, string $nama, bool $direkognisi, bool $lengkap) use ($prodi, $skema, $mhs) {
            $mk = Matakuliah::create(['prodi_id' => $prodi->id, 'kode' => $kode, 'nama' => $nama, 'sks' => 3, 'skor_minimum' => 66, 'dapat_direkognisi' => true, 'is_aktif' => true]);
            $skema->matakuliah()->attach($mk->id, ['direkognisi' => $direkognisi]);
            $cpmk = Cpmk::create(['matakuliah_id' => $mk->id, 'kode' => 'CPMK1', 'deskripsi' => 'Mampu', 'urutan' => 1]);
            $p = PengajuanMk::create(['mahasiswa_id' => $mhs->id, 'matakuliah_id' => $mk->id, 'nilai_mandiri_angka' => 80, 'nilai_mandiri_huruf' => 'AB']);
            if ($lengkap) {
                $p->bukti()->attach($mhs->bukti()->create(['kategori' => 'informal', 'jenis_bukti' => 'Sertifikat', 'judul' => $nama, 'url' => 'https://contoh.test/'.$kode])->id);
            }
            SelfAssessment::create(['pengajuan_mk_id' => $p->id, 'cpmk_id' => $cpmk->id, 'status' => $lengkap ? 'mampu' : null]);

            return $mk;
        };
        $diakui = $buat('MKB001', 'Akuntansi Biaya', true, true);
        $wajib = $buat('MKB56414', 'Anggaran Perusahaan', false, false);

        $uji = Livewire::actingAs($user)->test(Wizard::class);
        $daftar = $uji->viewData('pengajuanList');
        $this->assertTrue($daftar->has($diakui->id));
        $this->assertFalse($daftar->has($wajib->id));

        // The incomplete wajib course no longer blocks the next steps.
        $uji->set('step', 3)->call('lanjutKeForm4')->assertSet('step', 4);
        $uji->set('step', 5)->call('lanjutKeForm6')->assertSet('step', 6);

        // "Check all" leaves the hidden course alone; its record is kept.
        $uji->call('centangSemuaMampuGlobal');
        $this->assertNull(SelfAssessment::whereHas('pengajuanMk', fn ($q) => $q->where('matakuliah_id', $wajib->id))->value('status'));
        $this->assertSame(2, PengajuanMk::where('mahasiswa_id', $mhs->id)->count());
    }
}
