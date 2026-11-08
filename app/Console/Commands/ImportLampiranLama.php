<?php

namespace App\Console\Commands;

use App\Models\LampiranLama;
use App\Models\Mahasiswa;
use App\Services\LampiranLamaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Records which legacy upload belongs to which migrated student, so admins
 * can download them from "Arsip Sistem Lama". Only inserts lampiran_lama
 * rows (never touches other data) and is safe to re-run. It also reports
 * how many of the files are actually present in rpl.lampiran_lama_path.
 */
class ImportLampiranLama extends Command
{
    protected $signature = 'sirama:import-lampiran-lama {--dry-run : hitung saja, tanpa menyimpan}';

    protected $description = 'Catat lampiran (file upload) mahasiswa dari database sirama lama untuk diunduh di menu Arsip Sistem Lama';

    public function handle(LampiranLamaService $service): int
    {
        ImportSiramaLegacy::daftarkanKoneksiLegacy();

        $lokasi = config('rpl.lampiran_lama_path');
        $this->line("Folder file lama: {$lokasi}".(is_dir($lokasi) ? '' : '  <comment>(belum ada)</comment>'));

        $baris = DB::connection('sirama_legacy')->table('attachment as a')
            ->join('mahasiswa as m', 'm.id', '=', 'a.mahasiswa_id')
            ->select('a.*', 'm.email as mahasiswa_email')
            ->orderBy('a.id')
            ->get();

        $this->line("  {$baris->count()} lampiran tercatat di database lama.");

        $mahasiswa = [];
        $hasil = ['baru' => 0, 'sudah_ada' => 0, 'mhs_tidak_ditemukan' => 0, 'file_ada' => 0, 'file_hilang' => 0];
        $ukuran = 0;

        DB::beginTransaction();

        try {
            foreach ($baris as $b) {
                $mahasiswa[$b->mahasiswa_id] ??= ImportSiramaLegacy::cariMahasiswa($b->mahasiswa_id, $b->mahasiswa_email) ?? false;

                if (! $mahasiswa[$b->mahasiswa_id]) {
                    $hasil['mhs_tidak_ditemukan']++;

                    continue;
                }

                $lampiran = LampiranLama::firstOrNew(['legacy_id' => $b->id]);
                $lampiran->exists ? $hasil['sudah_ada']++ : $hasil['baru']++;

                $lampiran->fill([
                    'mahasiswa_id' => $mahasiswa[$b->mahasiswa_id]->id,
                    'label' => $b->label,
                    'nama_file' => $b->file_name,
                    'path' => $b->file_path,
                    'mime_type' => $b->mime_type,
                    'ukuran' => (int) $b->file_size,
                ])->save();

                if ($service->pathAbsolut($lampiran)) {
                    $hasil['file_ada']++;
                    $ukuran += (int) $b->file_size;
                } else {
                    $hasil['file_hilang']++;
                }
            }

            $tautan = $this->tautkanKeMatakuliah($mahasiswa);

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
            ['Lampiran baru '.($this->option('dry-run') ? '(akan dicatat)' : 'dicatat'), $hasil['baru']],
            ['Sudah tercatat sebelumnya (diperbarui)', $hasil['sudah_ada']],
            ['Mahasiswa di luar sistem baru (dilewati)', $hasil['mhs_tidak_ditemukan']],
            ['File ditemukan di folder', $hasil['file_ada'].' ('.round($ukuran / 1048576).' MB)'],
            ['File TIDAK ditemukan di folder', $hasil['file_hilang']],
            ['Tautan file ke mata kuliah '.($this->option('dry-run') ? '(akan dibuat)' : 'baru'), $tautan['baru']],
            ['Tautan sudah ada sebelumnya', $tautan['sudah_ada']],
            ['Tautan dilewati (mata kuliah tidak diajukan di sistem baru)', $tautan['dilewati']],
        ]);

        return self::SUCCESS;
    }

    /**
     * Rebuilds which file backed which course (legacy mata_kuliah_attachment)
     * so Form 5/7 can show the real evidence per course. A legacy pick is
     * matched to our submission by student + course code, as in import-mk-asal.
     *
     * @param  array<int, Mahasiswa|false>  $mahasiswa  legacy mahasiswa id => our student
     * @return array{baru: int, sudah_ada: int, dilewati: int}
     */
    private function tautkanKeMatakuliah(array $mahasiswa): array
    {
        $hasil = ['baru' => 0, 'sudah_ada' => 0, 'dilewati' => 0];
        $lampiranPerLegacyId = LampiranLama::pluck('id', 'legacy_id');

        $baris = DB::connection('sirama_legacy')->table('mata_kuliah_attachment as mka')
            ->join('mata_kuliah_pilihan as mp', 'mp.id', '=', 'mka.mata_kuliah_pilihan_id')
            ->join('mata_kuliah_semester as mks', 'mks.id', '=', 'mp.mata_kuliah_semester_id')
            ->join('mata_kuliah as mk', 'mk.id', '=', 'mks.mata_kuliah_id')
            ->select('mka.attachment_id', 'mp.mahasiswa_id', 'mk.kode_mk')
            ->distinct()
            ->get();

        $pengajuanPerKode = [];

        foreach ($baris as $b) {
            $lampiranId = $lampiranPerLegacyId[$b->attachment_id] ?? null;
            $mhs = $mahasiswa[$b->mahasiswa_id] ?? false;

            if (! $lampiranId || ! $mhs) {
                $hasil['dilewati']++;

                continue;
            }

            $pengajuanPerKode[$mhs->id] ??= $mhs->pengajuanMk()->join('matakuliah', 'matakuliah.id', '=', 'pengajuan_mk.matakuliah_id')
                ->pluck('pengajuan_mk.id', 'matakuliah.kode');
            $pengajuanId = $pengajuanPerKode[$mhs->id][$b->kode_mk] ?? null;

            if (! $pengajuanId) {
                $hasil['dilewati']++;

                continue;
            }

            $dibuat = DB::table('pengajuan_lampiran_lama')->insertOrIgnore([
                'pengajuan_mk_id' => $pengajuanId,
                'lampiran_lama_id' => $lampiranId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $dibuat ? $hasil['baru']++ : $hasil['sudah_ada']++;
        }

        return $hasil;
    }
}
