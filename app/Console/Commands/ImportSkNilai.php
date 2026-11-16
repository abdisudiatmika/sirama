<?php

namespace App\Console\Commands;

use App\Models\HasilPleno;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\PengajuanMk;
use App\Models\Penilaian;
use App\Models\Prodi;
use App\Services\PenilaianService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\IOFactory;

/**
 * Writes the final scores from a Director's SK (RPL results, .docx) into the
 * database. The SK lists one table per student ("N. Name", then Kode MK |
 * Nama | SKS | ... | Nilai Akhir | Huruf). The SK's final score becomes every
 * assigned assessor's score, so their average equals the SK exactly.
 *
 * Courses in the SK are marked Direkognisi; the student's other submitted
 * courses become Tidak Direkognisi. Courses in the SK the student never
 * submitted are created. Safe to re-run: it writes the same values again.
 */
class ImportSkNilai extends Command
{
    protected $signature = 'sirama:import-sk-nilai
        {file : path file SK (.docx)}
        {--prodi= : kode prodi, mis. AKM}
        {--dry-run : jalankan lalu batalkan semua perubahan}';

    protected $description = 'Impor nilai akhir dan keputusan rekognisi dari SK Direktur (.docx) ke database';

    public function handle(PenilaianService $penilaianService): int
    {
        $prodi = Prodi::where('kode', $this->option('prodi'))->first();
        if (! $prodi) {
            $this->error('Prodi tidak ditemukan. Gunakan --prodi=KODE, pilihan: '.Prodi::pluck('kode')->implode(', '));

            return self::FAILURE;
        }

        if (! is_file($this->argument('file'))) {
            $this->error('File tidak ditemukan: '.$this->argument('file'));

            return self::FAILURE;
        }

        ['nomor' => $nomorSk, 'tanggal' => $tanggalSk, 'mahasiswa' => $daftar] = $this->bacaSk($this->argument('file'));

        $this->line('SK nomor  : '.($nomorSk ?? '(tidak terbaca)'));
        $this->line('Tanggal   : '.($tanggalSk?->toDateString() ?? '(tidak terbaca)'));
        $this->line('Mahasiswa : '.count($daftar).', baris nilai: '.array_sum(array_map(fn ($m) => count($m['baris']), $daftar)));

        $catatanSk = 'SK Direktur'.($nomorSk ? ' No. '.$nomorSk : '');
        $ringkasan = [];
        $masalah = [];

        DB::beginTransaction();

        try {
            foreach ($daftar as $sk) {
                $mahasiswa = $this->cariMahasiswa($sk['nama'], $prodi->id, $masalah);
                if (! $mahasiswa) {
                    continue;
                }

                $penugasan = $mahasiswa->penugasanAsesor()->orderBy('urutan')->get();
                if ($penugasan->isEmpty()) {
                    $masalah[] = "{$sk['nama']}: belum punya asesor, nilai tidak bisa dimasukkan.";

                    continue;
                }

                $hitung = ['nilai' => 0, 'pengajuan_baru' => 0, 'direkognisi' => 0, 'tidak' => 0];
                $diakui = [];

                foreach ($sk['baris'] as $baris) {
                    $matakuliah = Matakuliah::where('prodi_id', $prodi->id)->where('kode', $baris['kode'])->first();
                    if (! $matakuliah) {
                        $masalah[] = "{$sk['nama']}: kode MK {$baris['kode']} ({$baris['nama']}) tidak ada di prodi {$prodi->kode}.";

                        continue;
                    }

                    $pengajuan = PengajuanMk::firstOrNew(['mahasiswa_id' => $mahasiswa->id, 'matakuliah_id' => $matakuliah->id]);
                    if (! $pengajuan->exists) {
                        // The student never submitted this course; the SK is the only record of it.
                        $pengajuan->fill([
                            'nilai_mandiri_angka' => (int) round($baris['nilai']),
                            'nilai_mandiri_huruf' => $baris['huruf'],
                        ])->save();
                        $hitung['pengajuan_baru']++;
                    }

                    $hitung['nilai'] += $this->tulisNilai($pengajuan, $penugasan->pluck('asesor_id'), $baris['nilai']);

                    HasilPleno::updateOrCreate(
                        ['mahasiswa_id' => $mahasiswa->id, 'matakuliah_id' => $matakuliah->id],
                        ['status' => 'Direkognisi', 'catatan_pleno' => $catatanSk, 'tanggal_pleno' => $tanggalSk],
                    );
                    $diakui[] = $matakuliah->id;
                    $hitung['direkognisi']++;

                    $rata = $penilaianService->rataRataAsesmen($pengajuan->fresh())['rata_rata'];
                    if (abs($rata - $baris['nilai']) > 0.005) {
                        $masalah[] = "{$sk['nama']} {$baris['kode']}: rata-rata {$rata} tidak sama dengan SK {$baris['nilai']}.";
                    }
                }

                foreach ($mahasiswa->pengajuanMk()->whereNotIn('matakuliah_id', $diakui)->get() as $lain) {
                    HasilPleno::updateOrCreate(
                        ['mahasiswa_id' => $mahasiswa->id, 'matakuliah_id' => $lain->matakuliah_id],
                        ['status' => 'Tidak Direkognisi', 'catatan_pleno' => 'Tidak tercantum dalam '.$catatanSk, 'tanggal_pleno' => $tanggalSk],
                    );
                    $hitung['tidak']++;
                }

                $mahasiswa->update(['status_berkas' => 'selesai_dinilai']);

                $ringkasan[] = [$sk['nama'], $mahasiswa->nim, $penugasan->count(), $hitung['direkognisi'], $hitung['tidak'], $hitung['pengajuan_baru'], $hitung['nilai']];
            }

            if ($this->option('dry-run')) {
                DB::rollBack();
                $this->warn('DRY RUN — semua perubahan dibatalkan, tidak ada yang disimpan.');
            } else {
                DB::commit();
                $this->info('Nilai SK tersimpan.');
            }
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Dibatalkan, tidak ada perubahan: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->table(['Mahasiswa', 'NIM', 'Asesor', 'MK Direkognisi', 'MK Tidak Direkognisi', 'Pengajuan dibuat', 'Baris nilai ditulis'], $ringkasan);

        foreach ($masalah as $m) {
            $this->warn('  ! '.$m);
        }

        return $masalah && ! $ringkasan ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Sets the SK score on every assigned assessor's rows for this course. Both
     * the raw score and nilai_akhir are set to it (not recomputed), so the
     * Form 6/7 per-route averages and the recap average all equal the SK.
     *
     * @return int rows written
     */
    private function tulisNilai(PengajuanMk $pengajuan, $asesorIds, float $nilai): int
    {
        $ditulis = 0;
        $jenisAda = Penilaian::where('pengajuan_mk_id', $pengajuan->id)->pluck('jenis')->unique();
        $jenisBaru = $jenisAda->contains('formal') || $pengajuan->mkAsal()->exists() || $pengajuan->bukti()->where('kategori', 'formal')->exists()
            ? 'formal'
            : ($jenisAda->first() ?? 'formal');

        foreach ($asesorIds as $asesorId) {
            $baris = Penilaian::where('pengajuan_mk_id', $pengajuan->id)->where('asesor_id', $asesorId)->get();

            if ($baris->isEmpty()) {
                Penilaian::create([
                    'pengajuan_mk_id' => $pengajuan->id,
                    'asesor_id' => $asesorId,
                    'jenis' => $jenisBaru,
                    'nilai' => $nilai,
                    'nilai_akhir' => $nilai,
                ]);
                $ditulis++;

                continue;
            }

            foreach ($baris as $p) {
                $p->update(['nilai' => $nilai, 'nilai_akhir' => $nilai]);
                $ditulis++;
            }
        }

        return $ditulis;
    }

    /** Matches the SK name to a student in the prodi, ignoring case, spacing and degrees after a comma. */
    private function cariMahasiswa(string $nama, int $prodiId, array &$masalah): ?Mahasiswa
    {
        $rapikan = fn (string $n) => mb_strtolower(trim(preg_replace('/\s+/u', ' ', explode(',', $n)[0])));
        $target = $rapikan($nama);

        $cocok = Mahasiswa::where('prodi_id', $prodiId)->with('user')->get()
            ->filter(fn ($m) => $rapikan($m->user->name) === $target);

        if ($cocok->count() === 1) {
            return $cocok->first();
        }

        $masalah[] = $cocok->isEmpty()
            ? "{$nama}: tidak ditemukan di prodi ini."
            : "{$nama}: ada {$cocok->count()} mahasiswa dengan nama sama, dilewati (NIM: ".$cocok->pluck('nim')->implode(', ').').';

        return null;
    }

    /** @return array{nomor: ?string, tanggal: ?Carbon, mahasiswa: list<array{nama: string, baris: list<array{kode: string, nama: string, nilai: float, huruf: string}>}>} */
    private function bacaSk(string $file): array
    {
        $word = IOFactory::load($file);
        $teks = function ($el) use (&$teks): string {
            if (method_exists($el, 'getElements')) {
                return implode('', array_map($teks, $el->getElements()));
            }

            return method_exists($el, 'getText') && is_string($el->getText()) ? $el->getText() : '';
        };

        $semuaTeks = '';
        $mahasiswa = [];

        foreach ($word->getSections() as $section) {
            foreach ($section->getElements() as $el) {
                if (! $el instanceof Table) {
                    $semuaTeks .= $teks($el)."\n";

                    continue;
                }

                $baris = array_map(
                    fn ($row) => array_map(fn ($cell) => trim(preg_replace('/\s+/u', ' ', $teks($cell))), $row->getCells()),
                    $el->getRows(),
                );
                $semuaTeks .= implode("\n", array_map(fn ($r) => implode(' ', $r), $baris))."\n";

                if (! preg_match('/^\d+\.\s*(.+)$/u', $baris[0][0] ?? '', $m)) {
                    continue;
                }

                $mhs = ['nama' => trim($m[1]), 'baris' => []];
                foreach (array_slice($baris, 1) as $r) {
                    if (count($r) < 5 || ! preg_match('/^\d+$/', $r[0]) || ! preg_match('/^[A-Z]{2,4}\d/', $r[1])) {
                        continue;
                    }
                    $nilai = collect(array_slice($r, 3))->first(fn ($c) => preg_match('/^\d{1,3}[.,]\d{1,2}$/', $c));
                    if ($nilai === null) {
                        continue;
                    }
                    $mhs['baris'][] = [
                        'kode' => $r[1],
                        'nama' => $r[2],
                        'nilai' => round((float) str_replace(',', '.', $nilai), 2),
                        'huruf' => (string) end($r),
                    ];
                }
                $mahasiswa[] = $mhs;
            }
        }

        $nomor = preg_match('/NOMOR\s*:?\s*([^\s]+)/iu', $semuaTeks, $m) ? $m[1] : null;
        $tanggal = null;
        // The enactment date ("Ditetapkan ... Pada Tanggal 13 Mei 2026") is the last such
        // phrase; earlier ones describe the assessment period.
        if (preg_match_all('/Pada\s+Tanggal\s+(\d{1,2})\s+(\p{L}+)\s+(\d{4})/iu', $semuaTeks, $semua, PREG_SET_ORDER)) {
            $m = end($semua);
            $bulan = ['januari' => 1, 'februari' => 2, 'maret' => 3, 'april' => 4, 'mei' => 5, 'juni' => 6, 'juli' => 7, 'agustus' => 8, 'september' => 9, 'oktober' => 10, 'november' => 11, 'desember' => 12][mb_strtolower($m[2])] ?? null;
            $tanggal = $bulan ? Carbon::create((int) $m[3], $bulan, (int) $m[1]) : null;
        }

        return ['nomor' => $nomor, 'tanggal' => $tanggal, 'mahasiswa' => $mahasiswa];
    }
}
