<?php

namespace App\Console\Commands;

use App\Models\Asesor;
use App\Models\Biodata;
use App\Models\Bukti;
use App\Models\Cpmk;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\PendidikanTerakhir;
use App\Models\PengajuanMk;
use App\Models\PenugasanAsesor;
use App\Models\Prodi;
use App\Models\SelfAssessment;
use App\Models\Skema;
use App\Models\User;
use App\Models\ValidasiCpmk;
use App\Services\PenilaianService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One-off migration from the legacy "sirama" system (a prior RPL platform
 * at the same institution) into this app's schema. Scoped to Akuntansi
 * Manajerial and Akuntansi Perpajakan only, per the source data's actual
 * shape: the legacy system tracked "LPD"/"Koperasi" cohorts as separate
 * `jurusan` rows even though they share one matakuliah catalog and one
 * real prodi each — confirmed by joining students' course picks back to
 * the catalog they actually resolve to.
 *
 * Evidence files aren't migrated (the legacy system stored uploaded PDFs
 * on its own server; only file paths — not the files — are in the dump).
 * Placeholder `bukti` rows are created instead, so the already-scored
 * assessments remain visible in this app's formal/informal-aware UI.
 */
class ImportSiramaLegacy extends Command
{
    protected $signature = 'sirama:import {--fresh-password= : override the random password prefix, for testing}';

    protected $description = 'Import mahasiswa/asesor/matakuliah/penilaian data from the legacy sirama_legacy MySQL database';

    /** @var array<int,int> legacy mata_kuliah.id => our matakuliah.id */
    private array $matakuliahMap = [];

    /** @var array<int,int> legacy cp_mata_kuliah.id => our cpmk.id */
    private array $cpmkMap = [];

    /** @var array<int,int> legacy jurusan.id => our skema.id */
    private array $skemaMap = [];

    /** @var array<int,int> legacy jurusan.id => our prodi.id */
    private array $prodiMap = [];

    /** @var array<int,int> legacy asesor.id => our asesor.id */
    private array $asesorMap = [];

    /** @var array<int,int> legacy mata_kuliah_pilihan.id => our pengajuan_mk.id */
    private array $pengajuanMap = [];

    /** @var array<int,int> legacy mahasiswa.id => our mahasiswa.id */
    private array $mahasiswaMap = [];

    /** @var array<int,string> used usernames this run, to keep generated ones unique */
    private array $usedUsernames = [];

    public static function daftarkanKoneksiLegacy(): void
    {
        Config::set('database.connections.sirama_legacy', [
            'driver' => 'mysql',
            'host' => env('LEGACY_DB_HOST', '127.0.0.1'),
            'port' => env('LEGACY_DB_PORT', 3306),
            'database' => env('LEGACY_DB_DATABASE', 'sirama_legacy'),
            'username' => env('LEGACY_DB_USERNAME', 'root'),
            'password' => env('LEGACY_DB_PASSWORD', 'Abdi085237'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);
    }

    public function handle(PenilaianService $penilaianService): int
    {
        self::daftarkanKoneksiLegacy();

        $this->info('Memulai impor data dari sirama_legacy...');

        DB::connection()->beginTransaction();

        try {
            $this->importProdiDanSkema();
            $this->importMatakuliahDanCpmk();
            $this->importAsesor();
            $this->importMahasiswa();
            $this->importPengajuanMk();
            $this->importBuktiPlaceholder();
            $this->importSelfAssessmentDanVatm();
            $this->importPenilaian($penilaianService);
            $this->finalisasiStatusBerkas();

            DB::connection()->commit();
            $this->info('Impor selesai dan disimpan.');
        } catch (\Throwable $e) {
            DB::connection()->rollBack();
            $this->error('Impor dibatalkan, semua perubahan di-rollback: '.$e->getMessage());
            $this->error($e->getTraceAsString());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function legacy(string $table)
    {
        return DB::connection('sirama_legacy')->table($table);
    }

    /**
     * jurusan 7/10/12 (kode AKM*) really are one prodi ("Akuntansi
     * Manajerial") with 3 cohorts (Reguler/LPD/Koperasi); jurusan 5 (AKP1)
     * is "Akuntansi Perpajakan" Reguler. Confirmed by joining students'
     * course picks back to the mata_kuliah catalog they resolve to.
     */
    private function importProdiDanSkema(): void
    {
        $this->line('Impor Prodi & Skema...');

        $akm = Prodi::firstOrCreate(['kode' => 'AKM'], ['nama' => 'Akuntansi Manajerial', 'jenjang' => 'D4', 'is_aktif' => true]);
        $akp = Prodi::firstOrCreate(['kode' => 'AKP'], ['nama' => 'Akuntansi Perpajakan', 'jenjang' => 'D3', 'is_aktif' => true]);

        $skemaDefinisi = [
            7 => ['prodi' => $akm, 'nama' => 'Akuntansi Manajerial — Reguler'],
            10 => ['prodi' => $akm, 'nama' => 'Akuntansi Manajerial — Skema LPD'],
            12 => ['prodi' => $akm, 'nama' => 'Akuntansi Manajerial — Skema Koperasi'],
            5 => ['prodi' => $akp, 'nama' => 'Akuntansi Perpajakan — Reguler'],
        ];

        foreach ($skemaDefinisi as $legacyJurusanId => $def) {
            $this->prodiMap[$legacyJurusanId] = $def['prodi']->id;

            $skema = Skema::firstOrCreate(
                ['prodi_id' => $def['prodi']->id, 'nama' => $def['nama'], 'tahun_akademik' => '2026/2027'],
                ['jenis' => 'RPL', 'is_aktif' => true]
            );
            $this->skemaMap[$legacyJurusanId] = $skema->id;
        }
    }

    /**
     * All AKM-family students' picks resolve to jurusan_id=12's catalog;
     * AKP students resolve to jurusan_id=5's own catalog. So only those
     * two jurusan_id values hold the "real" matakuliah masters to import.
     */
    private function importMatakuliahDanCpmk(): void
    {
        $this->line('Impor Matakuliah & CPMK...');

        $katalogJurusan = [12 => $this->prodiMap[12], 5 => $this->prodiMap[5]];

        foreach ($katalogJurusan as $legacyJurusanId => $prodiId) {
            $mataKuliahRows = $this->legacy('mata_kuliah')->where('jurusan_id', $legacyJurusanId)->get();

            foreach ($mataKuliahRows as $mk) {
                $matakuliah = Matakuliah::firstOrCreate(
                    ['prodi_id' => $prodiId, 'kode' => $mk->kode_mk],
                    [
                        'nama' => $mk->nama_mk,
                        'sks' => max(1, (int) $mk->sks),
                        'skor_minimum' => $mk->nilai_minimum ?? 66,
                        'dapat_direkognisi' => true,
                        'is_aktif' => (bool) $mk->status,
                    ]
                );
                $this->matakuliahMap[$mk->id] = $matakuliah->id;

                // Attach to every skema under this prodi (legacy had no
                // per-skema matakuliah linkage — mata_kuliah_skema was empty).
                foreach ($this->skemaMap as $legacyJId => $skemaId) {
                    if ($this->prodiMap[$legacyJId] === $prodiId) {
                        DB::table('skema_matakuliah')->insertOrIgnore([
                            'skema_id' => $skemaId,
                            'matakuliah_id' => $matakuliah->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                $cpmkRows = $this->legacy('cp_mata_kuliah as cpm')
                    ->join('mata_kuliah_semester as mks', 'mks.id', '=', 'cpm.mata_kuliah_semester_id')
                    ->where('mks.mata_kuliah_id', $mk->id)
                    ->select('cpm.id', 'cpm.indikator_capaian')
                    ->get();

                foreach ($cpmkRows as $i => $cpmkRow) {
                    $cpmk = Cpmk::create([
                        'matakuliah_id' => $matakuliah->id,
                        'kode' => 'CPMK'.($i + 1),
                        'deskripsi' => $cpmkRow->indikator_capaian,
                        'urutan' => $i + 1,
                    ]);
                    $this->cpmkMap[$cpmkRow->id] = $cpmk->id;
                }
            }
        }

        $this->info('  '.count($this->matakuliahMap).' matakuliah, '.count($this->cpmkMap).' CPMK.');
    }

    private function uniqueUsername(string $seed, int $legacyId): string
    {
        $base = Str::slug($seed, '') ?: 'user';
        $base = Str::limit($base, 30, '');
        $candidate = $base;
        $suffix = 1;

        while (
            in_array($candidate, $this->usedUsernames, true)
            || User::where('username', $candidate)->exists()
        ) {
            $candidate = $base.$suffix;
            $suffix++;
        }

        $this->usedUsernames[] = $candidate;

        return $candidate;
    }

    private function importAsesor(): void
    {
        $this->line('Impor Asesor...');

        $rows = $this->legacy('asesor as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->select('a.id', 'a.name', 'a.email as asesor_email', 'a.no_hp', 'u.email as user_email')
            ->get();

        foreach ($rows as $row) {
            $email = $row->asesor_email ?: $row->user_email ?: 'asesor'.$row->id.'@import.local';

            $user = User::where('email', $email)->first();

            if (! $user) {
                $user = User::create([
                    'name' => $row->name,
                    'username' => $this->uniqueUsername(explode('@', $email)[0], $row->id),
                    'email' => $email,
                    'password' => Hash::make(Str::password(16)),
                ]);
                $user->assignRole('asesor');
            } elseif (! $user->hasRole('asesor')) {
                $user->assignRole('asesor');
            }

            $asesor = Asesor::firstOrCreate(
                ['user_id' => $user->id],
                ['no_hp' => $row->no_hp, 'is_aktif' => true]
            );

            $this->asesorMap[$row->id] = $asesor->id;
        }

        $this->info('  '.count($this->asesorMap).' asesor.');
    }

    private function importMahasiswa(): void
    {
        $this->line('Impor Mahasiswa...');

        $rows = $this->legacy('mahasiswa as m')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->whereIn('u.jurusan_id', [5, 7, 10, 12])
            ->select('m.*', 'u.jurusan_id as legacy_jurusan_id')
            ->get();

        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $existingUser = User::where('email', $row->email)->first();

            if ($existingUser) {
                $existingMahasiswa = Mahasiswa::where('user_id', $existingUser->id)->first();

                if ($existingMahasiswa) {
                    $this->mahasiswaMap[$row->id] = $existingMahasiswa->id;
                }

                $bar->advance();

                continue;
            }

            $user = User::create([
                'name' => $row->name,
                'username' => $this->uniqueUsername(explode('@', $row->email)[0], $row->id),
                'email' => $row->email,
                'password' => Hash::make(Str::password(16)),
            ]);
            $user->assignRole('mahasiswa');

            $mahasiswa = Mahasiswa::create([
                'user_id' => $user->id,
                'prodi_id' => $this->prodiMap[$row->legacy_jurusan_id],
                'skema_id' => $this->skemaMap[$row->legacy_jurusan_id],
                'nim' => 'LEGACY-'.str_pad((string) $row->id, 5, '0', STR_PAD_LEFT),
                'tahun_masuk' => (int) date('Y', strtotime($row->created_at ?? 'now')),
                'status_berkas' => 'diajukan',
            ]);

            $this->mahasiswaMap[$row->id] = $mahasiswa->id;

            Biodata::create([
                'mahasiswa_id' => $mahasiswa->id,
                'nama_lengkap' => $row->name,
                'tempat_lahir' => $row->tempat_lahir,
                'tanggal_lahir' => $row->tgl_lahir,
                'jenis_kelamin' => $row->jenis_kelamin === 'P' ? 'Perempuan' : 'Laki-laki',
                'status' => $row->status_perkawinan === 'Kawin' ? 'Menikah' : 'Belum Menikah',
                'kebangsaan' => $row->kebangsaan ?: 'Indonesia',
                'alamat_rumah' => $row->alamat_rumah,
                'kode_pos' => preg_replace('/[^0-9]/', '', (string) $row->kode_pos) ?: null,
                'telp_rumah_hp' => $row->no_hp,
                'alamat_kantor' => $row->alamat_kantor,
                'email' => $row->email,
            ]);

            if (filled($row->nama_sekolah)) {
                PendidikanTerakhir::create([
                    'mahasiswa_id' => $mahasiswa->id,
                    'nama_sekolah' => $row->nama_sekolah,
                    'alamat_sekolah' => $row->alamat_sekolah,
                    'tahun_lulus_sekolah' => $row->tahun_lulus_sekolah ?: 2000,
                    'nama_pt' => $row->nama_pt,
                    'jurusan_prodi' => $row->prodi_pt,
                    'program' => in_array($row->program_pt, ['D3', 'D4', 'S1', 'S2']) ? $row->program_pt : null,
                    'tahun_lulus_pt' => $row->tahun_lulus_pt,
                ]);
            }

            $asesorIds = $this->legacy('asesor_mahasiswa')
                ->where('mahasiswa_id', $row->id)
                ->orderBy('id')
                ->limit(3)
                ->pluck('asesor_id');

            foreach ($asesorIds as $urutan => $legacyAsesorId) {
                if (! isset($this->asesorMap[$legacyAsesorId])) {
                    continue;
                }

                PenugasanAsesor::firstOrCreate(
                    ['mahasiswa_id' => $mahasiswa->id, 'urutan' => $urutan + 1],
                    ['asesor_id' => $this->asesorMap[$legacyAsesorId], 'assigned_at' => now()]
                );
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('  '.Mahasiswa::whereIn('prodi_id', array_unique($this->prodiMap))->count().' mahasiswa total di prodi terkait.');
    }

    private function importPengajuanMk(): void
    {
        $this->line('Impor Pengajuan Matakuliah...');

        $rows = $this->legacy('mata_kuliah_pilihan as mp')
            ->join('mahasiswa as m', 'm.id', '=', 'mp.mahasiswa_id')
            ->join('users as u', 'u.id', '=', 'm.user_id')
            ->join('mata_kuliah_semester as mks', 'mks.id', '=', 'mp.mata_kuliah_semester_id')
            ->whereIn('u.jurusan_id', [5, 7, 10, 12])
            ->select('mp.id', 'mp.mahasiswa_id as legacy_mahasiswa_id', 'mp.nilai_angka', 'mp.nilai_huruf', 'mks.mata_kuliah_id as legacy_matakuliah_id')
            ->get();

        $bar = $this->output->createProgressBar($rows->count());

        foreach ($rows as $row) {
            $mahasiswaId = $this->mahasiswaMap[$row->legacy_mahasiswa_id] ?? null;
            $matakuliahId = $this->matakuliahMap[$row->legacy_matakuliah_id] ?? null;

            if (! $mahasiswaId || ! $matakuliahId) {
                $bar->advance();

                continue;
            }

            $huruf = in_array($row->nilai_huruf, ['A', 'AB', 'B', 'BC', 'C', 'D', 'E']) ? $row->nilai_huruf : 'C';

            $pengajuan = PengajuanMk::updateOrCreate(
                ['mahasiswa_id' => $mahasiswaId, 'matakuliah_id' => $matakuliahId],
                [
                    'nilai_mandiri_angka' => min(100, max(0, (int) round($row->nilai_angka))),
                    'nilai_mandiri_huruf' => $huruf,
                ]
            );

            $this->pengajuanMap[$row->id] = $pengajuan->id;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('  '.count($this->pengajuanMap).' pengajuan matakuliah.');
    }

    /**
     * The legacy system stores actual uploaded files, which aren't in this
     * dump. We create one placeholder bukti per evidence category actually
     * assessed (per user's confirmed decision), so the imported scores stay
     * visible through this app's formal/informal-aware assessment UI.
     */
    private function importBuktiPlaceholder(): void
    {
        $this->line('Impor Bukti (placeholder)...');

        $legacyToOur = $this->pengajuanMap;

        $formalLegacyIds = $this->legacy('penilaian_transfer_sks as pts')
            ->join('transfer_sks as ts', 'ts.id', '=', 'pts.transfer_sks_id')
            ->whereIn('ts.mata_kuliah_pilihan_id', array_keys($legacyToOur))
            ->distinct()
            ->pluck('ts.mata_kuliah_pilihan_id');

        $informalLegacyIds = $this->legacy('penilaian_transfer_nonformal as ptn')
            ->join('transfer_sks_nonformal as tsn', 'tsn.id', '=', 'ptn.transfer_nonformal_id')
            ->whereIn('tsn.mata_kuliah_pilihan_id', array_keys($legacyToOur))
            ->distinct()
            ->pluck('tsn.mata_kuliah_pilihan_id');

        $count = 0;

        foreach (['formal' => $formalLegacyIds, 'informal' => $informalLegacyIds] as $kategori => $legacyIds) {
            foreach ($legacyIds as $legacyId) {
                $pengajuanId = $legacyToOur[$legacyId] ?? null;

                if (! $pengajuanId) {
                    continue;
                }

                $pengajuan = PengajuanMk::find($pengajuanId);

                $bukti = Bukti::create([
                    'mahasiswa_id' => $pengajuan->mahasiswa_id,
                    'kategori' => $kategori,
                    'jenis_bukti' => $kategori === 'formal' ? 'Ijazah' : 'Surat Pengalaman Kerja',
                    'judul' => 'Bukti '.ucfirst($kategori).' (migrasi data lama)',
                    'url' => 'DATA-LAMA: berkas asli tersimpan di sistem lama, mohon unggah ulang ke Google Drive',
                    'keterangan' => 'Diimpor dari sistem sirama lama — berkas asli tidak tersedia dalam bentuk link.',
                ]);

                DB::table('pengajuan_bukti')->insert([
                    'pengajuan_mk_id' => $pengajuanId,
                    'bukti_id' => $bukti->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $count++;
            }
        }

        $this->info("  {$count} bukti placeholder dibuat.");
    }

    private function importSelfAssessmentDanVatm(): void
    {
        $this->line('Impor Self-Assessment & Validasi VATM...');

        $rows = $this->legacy('cp_level_kompetensi')
            ->whereIn('mata_kuliah_pilihan_id', array_keys($this->pengajuanMap))
            ->whereIn('cp_mata_kuliah_id', array_keys($this->cpmkMap))
            ->get();

        $selfCount = 0;

        foreach ($rows as $row) {
            $pengajuanId = $this->pengajuanMap[$row->mata_kuliah_pilihan_id];
            $cpmkId = $this->cpmkMap[$row->cp_mata_kuliah_id];

            $status = match ($row->level_kompetensi) {
                1 => 'mampu',
                0 => 'belum_mampu',
                default => null,
            };

            SelfAssessment::updateOrCreate(
                ['pengajuan_mk_id' => $pengajuanId, 'cpmk_id' => $cpmkId],
                ['status' => $status]
            );
            $selfCount++;
        }

        $vatmRows = $this->legacy('penilaian_cp_kompetensi as pck')
            ->join('cp_level_kompetensi as clk', 'clk.id', '=', 'pck.cp_level_kompetensi_id')
            ->whereIn('clk.mata_kuliah_pilihan_id', array_keys($this->pengajuanMap))
            ->whereIn('clk.cp_mata_kuliah_id', array_keys($this->cpmkMap))
            ->select('pck.*', 'clk.mata_kuliah_pilihan_id', 'clk.cp_mata_kuliah_id')
            ->get();

        $vatmCount = 0;

        foreach ($vatmRows as $row) {
            $asesorId = $this->asesorMap[$row->asesor_id] ?? null;
            $pengajuanId = $this->pengajuanMap[$row->mata_kuliah_pilihan_id] ?? null;
            $cpmkId = $this->cpmkMap[$row->cp_mata_kuliah_id] ?? null;

            if (! $asesorId || ! $pengajuanId || ! $cpmkId) {
                continue;
            }

            ValidasiCpmk::updateOrCreate(
                ['pengajuan_mk_id' => $pengajuanId, 'asesor_id' => $asesorId, 'cpmk_id' => $cpmkId],
                [
                    'valid' => (bool) $row->valid,
                    'asli' => (bool) $row->asli,
                    'terkini' => (bool) $row->terkini,
                    'memadai' => (bool) $row->memadai,
                ]
            );
            $vatmCount++;
        }

        $this->info("  {$selfCount} self-assessment, {$vatmCount} validasi VATM.");
    }

    private function importPenilaian(PenilaianService $service): void
    {
        $this->line('Impor Penilaian Asesor...');

        $touched = [];

        $formalRows = $this->legacy('penilaian_transfer_sks as pts')
            ->join('transfer_sks as ts', 'ts.id', '=', 'pts.transfer_sks_id')
            ->whereIn('ts.mata_kuliah_pilihan_id', array_keys($this->pengajuanMap))
            ->whereNotNull('pts.hasil')
            ->select('pts.*', 'ts.mata_kuliah_pilihan_id')
            ->get();

        $bar = $this->output->createProgressBar($formalRows->count());

        foreach ($formalRows as $row) {
            $asesorId = $this->asesorMap[$row->asesor_id] ?? null;
            $pengajuanId = $this->pengajuanMap[$row->mata_kuliah_pilihan_id] ?? null;

            if (! $asesorId || ! $pengajuanId) {
                $bar->advance();

                continue;
            }

            DB::table('penilaian')->updateOrInsert(
                ['pengajuan_mk_id' => $pengajuanId, 'asesor_id' => $asesorId, 'jenis' => 'formal'],
                [
                    'nilai' => min(100, max(0, (int) $row->hasil)),
                    'catatan_reviewer' => $row->catatan_asesor,
                    'catatan_kesenjangan' => $row->kesenjangan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $touched[$pengajuanId.':'.$asesorId] = [$pengajuanId, $asesorId];
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $informalRows = $this->legacy('penilaian_transfer_nonformal as ptn')
            ->join('transfer_sks_nonformal as tsn', 'tsn.id', '=', 'ptn.transfer_nonformal_id')
            ->whereIn('tsn.mata_kuliah_pilihan_id', array_keys($this->pengajuanMap))
            ->whereNotNull('ptn.nilai')
            ->select('ptn.*', 'tsn.mata_kuliah_pilihan_id')
            ->get();

        $bar = $this->output->createProgressBar($informalRows->count());

        foreach ($informalRows as $row) {
            $asesorId = $this->asesorMap[$row->asesor_id] ?? null;
            $pengajuanId = $this->pengajuanMap[$row->mata_kuliah_pilihan_id] ?? null;

            if (! $asesorId || ! $pengajuanId) {
                $bar->advance();

                continue;
            }

            DB::table('penilaian')->updateOrInsert(
                ['pengajuan_mk_id' => $pengajuanId, 'asesor_id' => $asesorId, 'jenis' => 'informal'],
                [
                    'nilai' => min(100, max(0, (int) $row->nilai)),
                    'catatan_reviewer' => $row->catatan_asesor,
                    'catatan_kesenjangan' => $row->kesenjangan,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );

            $touched[$pengajuanId.':'.$asesorId] = [$pengajuanId, $asesorId];
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->line('  Menghitung ulang nilai akhir ('.count($touched).' pasangan pengajuan+asesor)...');
        $bar = $this->output->createProgressBar(count($touched));

        foreach ($touched as [$pengajuanId, $asesorId]) {
            $service->rekalkulasiNilaiAkhir(PengajuanMk::find($pengajuanId), Asesor::find($asesorId));
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        $this->info('  '.$formalRows->count().' nilai formal, '.$informalRows->count().' nilai informal.');
    }

    /**
     * Mirrors FormPenilaian's own status-flip rule (§9): sedang_dinilai once
     * any assessor has scored, selesai_dinilai once every assigned assessor
     * has finished every matakuliah.
     */
    private function finalisasiStatusBerkas(): void
    {
        $this->line('Finalisasi status berkas...');

        $mahasiswaList = Mahasiswa::whereIn('prodi_id', array_unique($this->prodiMap))
            ->with(['pengajuanMk.bukti', 'pengajuanMk.penilaian', 'penugasanAsesor'])
            ->get();

        foreach ($mahasiswaList as $mhs) {
            if ($mhs->pengajuanMk->isEmpty()) {
                $mhs->update(['status_berkas' => 'draft']);

                continue;
            }

            $adaNilai = $mhs->pengajuanMk->contains(fn ($p) => $p->penilaian->isNotEmpty());

            if (! $adaNilai) {
                continue;
            }

            $mhs->update(['status_berkas' => 'sedang_dinilai']);

            $asesorIds = $mhs->penugasanAsesor->pluck('asesor_id');
            $semuaSelesai = $asesorIds->isNotEmpty() && $asesorIds->every(
                fn ($asesorId) => $mhs->pengajuanMk->every(fn ($p) => $p->selesaiDinilaiOleh($asesorId))
            );

            if ($semuaSelesai) {
                $mhs->update(['status_berkas' => 'selesai_dinilai']);
            }
        }

        $this->info('  Status berkas diperbarui untuk '.$mahasiswaList->count().' mahasiswa.');
    }
}
