<?php

namespace Tests\Unit;

use App\Services\PenilaianService;
use Tests\TestCase;

/**
 * Covers the §6.1 combined-score formula with the exact examples from the
 * PRD's reference table, so a future policy change that breaks the 85-point
 * cap or the MAX(formal, ...) floor fails loudly instead of silently.
 */
class PenilaianServiceTest extends TestCase
{
    private PenilaianService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PenilaianService;

        config([
            'rpl.batas_nilai_gabungan' => 85,
            'rpl.bobot_informal' => 0.10,
        ]);
    }

    public function test_formal_saja(): void
    {
        $this->assertSame(82, $this->service->hitungNilaiAkhir(82, null));
    }

    public function test_informal_saja(): void
    {
        $this->assertSame(78, $this->service->hitungNilaiAkhir(null, 78));
    }

    public function test_tidak_ada_bukti(): void
    {
        $this->assertNull($this->service->hitungNilaiAkhir(null, null));
    }

    public function test_gabungan_di_bawah_batas(): void
    {
        // 75 + (0.1 * 80) = 83, di bawah batas 85
        $this->assertSame(83, $this->service->hitungNilaiAkhir(75, 80));
    }

    public function test_gabungan_dibatasi_85(): void
    {
        // 80 + (0.1 * 90) = 89, dibatasi ke 85
        $this->assertSame(85, $this->service->hitungNilaiAkhir(80, 90));
    }

    public function test_formal_di_atas_batas_tetap_dipakai(): void
    {
        // Gabungan 98 dibatasi 85, tapi formal 90 > 85, jadi 90 yang dipakai
        $this->assertSame(90, $this->service->hitungNilaiAkhir(90, 80));
    }

    public function test_formal_86_tetap_dipakai_walau_di_atas_batas(): void
    {
        // Gabungan 92 dibatasi 85, tapi formal 86 > 85, jadi 86 yang dipakai
        $this->assertSame(86, $this->service->hitungNilaiAkhir(86, 60));
    }

    public function test_konversi_huruf_batas_bawah_dan_atas(): void
    {
        config(['rpl.konversi_huruf' => [
            ['min' => 80, 'max' => 100, 'huruf' => 'A', 'bobot' => 4.00],
            ['min' => 71, 'max' => 79, 'huruf' => 'AB', 'bobot' => 3.50],
            ['min' => 65, 'max' => 70, 'huruf' => 'B', 'bobot' => 3.00],
            ['min' => 60, 'max' => 64, 'huruf' => 'BC', 'bobot' => 2.50],
            ['min' => 55, 'max' => 59, 'huruf' => 'C', 'bobot' => 2.00],
            ['min' => 40, 'max' => 54, 'huruf' => 'D', 'bobot' => 1.00],
            ['min' => 0, 'max' => 39, 'huruf' => 'E', 'bobot' => 0.00],
        ]]);

        $this->assertSame('A', $this->service->konversiHuruf(80)['huruf']);
        $this->assertSame('AB', $this->service->konversiHuruf(79)['huruf']);
        $this->assertSame('E', $this->service->konversiHuruf(0)['huruf']);
    }
}
