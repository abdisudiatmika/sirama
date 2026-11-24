<?php

namespace Tests\Feature;

use App\Livewire\AdminProdi\MahasiswaManager;
use App\Models\Asesor;
use App\Models\Mahasiswa;
use App\Models\PenugasanAsesor;
use App\Models\Prodi;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilterMahasiswaTest extends TestCase
{
    use RefreshDatabase;

    public function test_daftar_mahasiswa_bisa_difilter(): void
    {
        $prodi = Prodi::create(['kode' => 'AKM', 'nama' => 'Akuntansi Manajerial', 'jenjang' => 'D4', 'is_aktif' => true]);
        $lain = Prodi::create(['kode' => 'AKP', 'nama' => 'Akuntansi Perpajakan', 'jenjang' => 'D4', 'is_aktif' => true]);
        $reg = Skema::create(['prodi_id' => $prodi->id, 'nama' => 'Reguler', 'jenis' => 'RPL', 'tahun_akademik' => '2026/2027', 'is_aktif' => true]);
        $lpd = Skema::create(['prodi_id' => $prodi->id, 'nama' => 'Skema LPD', 'jenis' => 'RPL', 'tahun_akademik' => '2026/2027', 'is_aktif' => true]);
        $admin = User::factory()->create();
        $admin->prodiDikelola()->attach($prodi->id);

        $buat = function (string $nama, Prodi $p, ?Skema $s, int $tahun, string $status, bool $kunci = false, int $asesor = 0) {
            $m = Mahasiswa::create([
                'user_id' => User::factory()->create(['name' => $nama, 'is_locked' => $kunci])->id,
                'prodi_id' => $p->id, 'skema_id' => $s?->id, 'nim' => 'N-'.$nama, 'tahun_masuk' => $tahun, 'status_berkas' => $status,
            ]);
            for ($i = 1; $i <= $asesor; $i++) {
                $a = Asesor::create(['user_id' => User::factory()->create()->id, 'nidn' => $nama.$i, 'is_aktif' => true]);
                PenugasanAsesor::create(['mahasiswa_id' => $m->id, 'asesor_id' => $a->id, 'urutan' => $i, 'assigned_at' => now()]);
            }
        };
        $buat('Ayu', $prodi, $reg, 2026, 'selesai_dinilai', asesor: 3);
        $buat('Bagus', $prodi, $lpd, 2026, 'draft', kunci: true);
        $buat('Citra', $prodi, $reg, 2025, 'sedang_dinilai', asesor: 2);
        $buat('Ayu Lain', $lain, null, 2026, 'draft');

        $lihat = fn (array $set) => collect(array_reduce(
            array_keys($set),
            fn ($t, $k) => $t->set($k, $set[$k]),
            Livewire::actingAs($admin)->test(MahasiswaManager::class)
        )->viewData('mahasiswaList')->items())->map(fn ($m) => $m->user->name)->sort()->values()->all();

        $this->assertSame(['Ayu', 'Bagus', 'Citra'], $lihat([]));
        $this->assertSame(['Ayu'], $lihat(['search' => 'Ayu'])); // other prodi's "Ayu Lain" stays out
        $this->assertSame(['Ayu', 'Citra'], $lihat(['filterSkemaId' => $reg->id]));
        $this->assertSame(['Citra'], $lihat(['filterAngkatan' => 2025]));
        $this->assertSame(['Bagus'], $lihat(['filterStatus' => 'draft']));
        $this->assertSame(['Ayu'], $lihat(['filterAsesor' => 'lengkap']));
        $this->assertSame(['Citra'], $lihat(['filterAsesor' => 'kurang']));
        $this->assertSame(['Bagus'], $lihat(['filterAsesor' => 'belum']));
        $this->assertSame(['Bagus'], $lihat(['filterAkses' => 'terkunci']));
        $this->assertSame(['Ayu'], $lihat(['filterSkemaId' => $reg->id, 'filterAngkatan' => 2026]));
    }
}
