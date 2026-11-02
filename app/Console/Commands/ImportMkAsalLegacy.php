<?php

namespace App\Console\Commands;

use App\Models\Mahasiswa;
use App\Models\MkAsal;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfills prior-course data (mk_asal) for students migrated by
 * sirama:import, which skipped it. Only inserts mk_asal rows — it never
 * touches submissions, scores or anything students may have edited since,
 * and re-running it adds nothing already present.
 *
 * The legacy transfer_sks table re-inserted every row on each save
 * (~230k rows, ~6.5k distinct), so rows are deduplicated per course.
 */
class ImportMkAsalLegacy extends Command
{
    protected $signature = 'sirama:import-mk-asal {--dry-run : hitung saja, tanpa menyimpan}';

    protected $description = 'Impor data mata kuliah asal (Form 6) dari database sirama lama untuk mahasiswa hasil migrasi';

    public function handle(): int
    {
        ImportSiramaLegacy::daftarkanKoneksiLegacy();
        $legacy = DB::connection('sirama_legacy');
        $legacy->statement('SET SESSION group_concat_max_len = 1000000');

        $this->info('Membaca data transfer_sks dari database lama...');

        $baris = $legacy->table('transfer_sks as ts')
            ->join('mata_kuliah_pilihan as mp', 'mp.id', '=', 'ts.mata_kuliah_pilihan_id')
            ->join('mata_kuliah_semester as mks', 'mks.id', '=', 'mp.mata_kuliah_semester_id')
            ->join('mata_kuliah as mk', 'mk.id', '=', 'mks.mata_kuliah_id')
            ->join('mahasiswa as m', 'm.id', '=', 'mp.mahasiswa_id')
            ->leftJoin('transfer_sks_cpmk as tsc', 'tsc.transfer_sks_id', '=', 'ts.id')
            ->groupBy('m.id', 'm.email', 'mk.kode_mk', DB::raw('TRIM(ts.kode_mk_asal)'), DB::raw('TRIM(ts.nama_mk_asal)'))
            ->select([
                'm.id as legacy_mahasiswa_id',
                'm.email',
                'mk.kode_mk',
                DB::raw('TRIM(ts.kode_mk_asal) as kode_asal'),
                DB::raw('TRIM(ts.nama_mk_asal) as nama_asal'),
                DB::raw("GROUP_CONCAT(DISTINCT TRIM(tsc.cpmk) ORDER BY tsc.id SEPARATOR '\n') as cpmk"),
            ])
            ->get();

        $this->line("  {$baris->count()} kombinasi unik mata kuliah asal.");

        $mahasiswaPerLegacyId = $this->petaMahasiswa($baris);
        $hasil = ['baru' => 0, 'sudah_ada' => 0, 'mhs_tidak_ditemukan' => 0, 'pengajuan_tidak_ditemukan' => 0];
        $pengajuanTersentuh = [];

        DB::beginTransaction();

        try {
            foreach ($baris as $b) {
                $mahasiswa = $mahasiswaPerLegacyId[$b->legacy_mahasiswa_id] ?? null;

                if (! $mahasiswa) {
                    $hasil['mhs_tidak_ditemukan']++;

                    continue;
                }

                $pengajuan = $mahasiswa->pengajuanMk->first(fn ($p) => $p->matakuliah->kode === $b->kode_mk);

                if (! $pengajuan) {
                    $hasil['pengajuan_tidak_ditemukan']++;

                    continue;
                }

                $mkAsal = MkAsal::firstOrNew([
                    'pengajuan_mk_id' => $pengajuan->id,
                    'kode_asal' => $b->kode_asal,
                    'nama_mk_asal' => $b->nama_asal,
                ]);

                if ($mkAsal->exists) {
                    $hasil['sudah_ada']++;

                    continue;
                }

                $mkAsal->cpmk_asal = (string) $b->cpmk;
                $mkAsal->save();
                $hasil['baru']++;
                $pengajuanTersentuh[$pengajuan->id] = true;
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('DRY RUN — tidak ada yang disimpan.');
            } else {
                DB::commit();
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Dibatalkan, tidak ada perubahan: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Keterangan', 'Jumlah'], [
            ['MK asal baru '.($this->option('dry-run') ? '(akan dibuat)' : 'dibuat'), $hasil['baru']],
            ['Pengajuan yang mendapat MK asal', count($pengajuanTersentuh)],
            ['Sudah ada sebelumnya (dilewati)', $hasil['sudah_ada']],
            ['Mahasiswa tidak ditemukan di sistem baru', $hasil['mhs_tidak_ditemukan']],
            ['Mata kuliah tidak diajukan di sistem baru', $hasil['pengajuan_tidak_ditemukan']],
        ]);

        return self::SUCCESS;
    }

    /** @return array<int, Mahasiswa> */
    private function petaMahasiswa($baris): array
    {
        $peta = [];

        foreach ($baris->unique('legacy_mahasiswa_id') as $b) {
            $mahasiswa = ImportSiramaLegacy::cariMahasiswa($b->legacy_mahasiswa_id, $b->email);

            if ($mahasiswa) {
                $peta[$b->legacy_mahasiswa_id] = $mahasiswa->load('pengajuanMk.matakuliah');
            }
        }

        return $peta;
    }
}
