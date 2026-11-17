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
        $this->assertSame(82.0, $this->service->hitungNilaiAkhir(82, null));
    }

    public function test_informal_saja(): void
    {
        $this->assertSame(78.0, $this->service->hitungNilaiAkhir(null, 78));
    }

    public function test_tidak_ada_bukti(): void
    {
        $this->assertNull($this->service->hitungNilaiAkhir(null, null));
    }

    public function test_gabungan_di_bawah_batas(): void
    {
        // 75 + (0.1 * 80) = 83, di bawah batas 85
        $this->assertSame(83.0, $this->service->hitungNilaiAkhir(75, 80));
    }

    public function test_gabungan_dibatasi_85(): void
    {
        // 80 + (0.1 * 90) = 89, dibatasi ke 85
        $this->assertSame(85.0, $this->service->hitungNilaiAkhir(80, 90));
    }

    public function test_formal_di_atas_batas_tetap_dipakai(): void
    {
        // Gabungan 98 dibatasi 85, tapi formal 90 > 85, jadi 90 yang dipakai
        $this->assertSame(90.0, $this->service->hitungNilaiAkhir(90, 80));
    }

    public function test_formal_86_tetap_dipakai_walau_di_atas_batas(): void
    {
        // Gabungan 92 dibatasi 85, tapi formal 86 > 85, jadi 86 yang dipakai
        $this->assertSame(86.0, $this->service->hitungNilaiAkhir(86, 60));
    }

    public function test_gabungan_menyimpan_desimal(): void
    {
        // 75 + (0.1 * 83) = 83.3 — kept, not truncated to 83
        $this->assertSame(83.3, $this->service->hitungNilaiAkhir(75, 83));
    }

    public function test_konversi_huruf_mengikuti_tabel_pnb_dengan_desimal(): void
    {
        config(['rpl.konversi_huruf' => [
            ['min' => 81, 'max' => 100, 'huruf' => 'A', 'bobot' => 4.00],
            ['min' => 76, 'max' => 80, 'huruf' => 'AB', 'bobot' => 3.50],
            ['min' => 71, 'max' => 75, 'huruf' => 'B', 'bobot' => 3.00],
            ['min' => 66, 'max' => 70, 'huruf' => 'BC', 'bobot' => 2.50],
            ['min' => 61, 'max' => 65, 'huruf' => 'C', 'bobot' => 2.00],
            ['min' => 41, 'max' => 60, 'huruf' => 'D', 'bobot' => 1.00],
            ['min' => 0, 'max' => 40, 'huruf' => 'E', 'bobot' => 0.00],
        ]]);

        // Cases taken from the official SK Gelombang 1 AKM 2026/2027.
        $this->assertSame('A', $this->service->konversiHuruf(81)['huruf']);
        $this->assertSame('AB', $this->service->konversiHuruf(80.93)['huruf']);
        $this->assertSame('AB', $this->service->konversiHuruf(76)['huruf']);
        $this->assertSame('B', $this->service->konversiHuruf(75.83)['huruf']);
        $this->assertSame('B', $this->service->konversiHuruf(72.23)['huruf']);
        $this->assertSame('D', $this->service->konversiHuruf(60.99)['huruf']);
        $this->assertSame('E', $this->service->konversiHuruf(40.5)['huruf']);
        $this->assertSame(3.5, $this->service->konversiHuruf(80.67)['bobot']);
    }

    public function test_huruf_akhir_memakai_huruf_sk_bila_ada(): void
    {
        // SK Gelombang 3 AKP grades 64.33 as BC although the table gives C.
        $this->assertSame('BC', $this->service->hurufAkhir(64.33, 'BC')['huruf']);
        $this->assertSame(2.5, (float) $this->service->hurufAkhir(64.33, 'BC')['bobot']);
        $this->assertSame('C', $this->service->hurufAkhir(64.33)['huruf']);
        $this->assertSame('C', $this->service->hurufAkhir(64.33, 'XX')['huruf']);
        $this->assertNull($this->service->hurufAkhir(null));
    }
}
