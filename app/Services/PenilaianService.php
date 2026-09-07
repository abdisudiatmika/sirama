<?php

namespace App\Services;

use App\Models\Asesor;
use App\Models\PengajuanMk;
use App\Models\Penilaian;

/**
 * Single source of truth for the RPL scoring rules (PRD §6.1 and §6.4).
 * Kept out of controllers/Livewire/Blade because the 85-point cap and the
 * MAX(formal, ...) rule are the parts of this system most likely to change
 * with policy, and the most likely to be silently reimplemented wrong.
 */
class PenilaianService
{
    /**
     * §6.1 — combine an asesor's formal/informal scores for one matakuliah.
     *
     *   Formal only:   nilai_akhir = nilai_formal
     *   Informal only:  nilai_akhir = nilai_informal
     *   Both:          gabungan = formal + (bobot_informal * informal), capped at batas
     *                  nilai_akhir = MAX(formal, MIN(gabungan, batas))
     */
    public function hitungNilaiAkhir(?int $nilaiFormal, ?int $nilaiInformal): ?int
    {
        if ($nilaiFormal === null && $nilaiInformal === null) {
            return null;
        }

        if ($nilaiFormal === null) {
            return $nilaiInformal;
        }

        if ($nilaiInformal === null) {
            return $nilaiFormal;
        }

        $batas = (int) config('rpl.batas_nilai_gabungan');
        $bobot = (float) config('rpl.bobot_informal');

        $gabungan = $nilaiFormal + ($bobot * $nilaiInformal);

        return (int) max($nilaiFormal, min($gabungan, $batas));
    }

    /**
     * Convert a numeric score to its institutional letter grade + weight,
     * using the configurable table in config('rpl.konversi_huruf').
     */
    public function konversiHuruf(int $nilai): array
    {
        $tabel = config('rpl.konversi_huruf');

        foreach ($tabel as $range) {
            if ($nilai >= $range['min'] && $nilai <= $range['max']) {
                return $range;
            }
        }

        return end($tabel);
    }

    /**
     * Save one asesor's score for one jenis (formal/informal), then
     * recompute and mirror the combined nilai_akhir onto every jenis row
     * for that asesor+pengajuan_mk pair (§7.2 caching rationale).
     */
    public function simpanPenilaian(
        PengajuanMk $pengajuanMk,
        Asesor $asesor,
        string $jenis,
        int $nilai,
        ?string $catatanReviewer,
        ?string $catatanKesenjangan,
    ): Penilaian {
        $penilaian = Penilaian::updateOrCreate(
            [
                'pengajuan_mk_id' => $pengajuanMk->id,
                'asesor_id' => $asesor->id,
                'jenis' => $jenis,
            ],
            [
                'nilai' => $nilai,
                'catatan_reviewer' => $catatanReviewer,
                'catatan_kesenjangan' => $catatanKesenjangan,
            ]
        );

        $this->rekalkulasiNilaiAkhir($pengajuanMk, $asesor);

        return $penilaian->fresh();
    }

    /**
     * Recompute nilai_akhir for an asesor's rows on this pengajuan_mk and
     * write it back to all of them (there are at most 2: formal, informal).
     */
    public function rekalkulasiNilaiAkhir(PengajuanMk $pengajuanMk, Asesor $asesor): void
    {
        $rows = Penilaian::where('pengajuan_mk_id', $pengajuanMk->id)
            ->where('asesor_id', $asesor->id)
            ->get()
            ->keyBy('jenis');

        $nilaiFormal = $rows->get('formal')?->nilai;
        $nilaiInformal = $rows->get('informal')?->nilai;

        $nilaiAkhir = $this->hitungNilaiAkhir($nilaiFormal, $nilaiInformal);

        Penilaian::where('pengajuan_mk_id', $pengajuanMk->id)
            ->where('asesor_id', $asesor->id)
            ->update(['nilai_akhir' => $nilaiAkhir]);
    }

    /**
     * §6.4 — average across asesors who have actually scored this
     * matakuliah (not the number assigned), rounded half-up. Returns null
     * average when nobody has scored yet, per the PRD's explicit rule that
     * an empty average must never render as 0.
     */
    public function rataRataAsesmen(PengajuanMk $pengajuanMk): array
    {
        $nilaiPerAsesor = Penilaian::where('pengajuan_mk_id', $pengajuanMk->id)
            ->whereNotNull('nilai_akhir')
            ->get()
            ->groupBy('asesor_id')
            ->map(fn ($rows) => $rows->first()->nilai_akhir);

        $jumlahPenilai = $nilaiPerAsesor->count();

        if ($jumlahPenilai === 0) {
            return ['rata_rata' => null, 'jumlah_penilai' => 0];
        }

        $rataRata = (int) round($nilaiPerAsesor->sum() / $jumlahPenilai, 0, PHP_ROUND_HALF_UP);

        return ['rata_rata' => $rataRata, 'jumlah_penilai' => $jumlahPenilai];
    }
}
