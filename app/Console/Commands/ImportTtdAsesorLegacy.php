<?php

namespace App\Console\Commands;

use App\Models\Asesor;
use App\Models\User;
use App\Services\TandaTanganAsesorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Copies the signatures assessors uploaded in the legacy sirama
 * (asesor.signature, relative to its storage/app/public) onto the matching
 * new assessor, matched by email first and then by name like the main import.
 */
class ImportTtdAsesorLegacy extends Command
{
    protected $signature = 'sirama:import-ttd-legacy
        {folder : folder storage/app/public sistem lama (berisi signatures/...)}
        {--timpa : ganti TTD yang sudah ada di sistem baru}
        {--dry-run : tampilkan saja, tanpa menyimpan}';

    protected $description = 'Salin tanda tangan asesor dari sistem sirama lama ke sistem baru';

    public function handle(TandaTanganAsesorService $ttd): int
    {
        ImportSiramaLegacy::daftarkanKoneksiLegacy();
        $folder = rtrim($this->argument('folder'), '/');

        $rows = DB::connection('sirama_legacy')->table('asesor as a')
            ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->whereNotNull('a.signature')->where('a.signature', '<>', '')
            ->select('a.id', 'a.name', 'a.email as asesor_email', 'u.email as user_email', 'a.signature')
            ->orderBy('a.id')
            ->get();

        $hasil = ['disalin' => 0, 'sudah_ada' => 0, 'tidak_cocok' => 0, 'file_hilang' => 0];
        foreach ($rows as $row) {
            $email = $row->asesor_email ?: $row->user_email;
            $user = ($email ? User::where('email', $email)->first() : null)
                ?? User::role('asesor')->where('name', $row->name)->first();
            $asesor = $user ? Asesor::where('user_id', $user->id)->first() : null;
            $file = $folder.'/'.ltrim($row->signature, '/');

            if (! $asesor) {
                $this->warn("  ! {$row->name}: tidak ada asesor yang cocok di sistem baru");
                $hasil['tidak_cocok']++;
            } elseif (! is_file($file)) {
                $this->warn("  ! {$row->name}: file {$row->signature} tidak ditemukan");
                $hasil['file_hilang']++;
            } elseif ($asesor->tanda_tangan && ! $this->option('timpa')) {
                $this->line("  = {$row->name}: sudah punya TTD di sistem baru, dilewati");
                $hasil['sudah_ada']++;
            } else {
                $this->line("  + {$row->name}");
                if (! $this->option('dry-run')) {
                    $ttd->simpan($asesor, $file);
                }
                $hasil['disalin']++;
            }
        }

        $this->info(($this->option('dry-run') ? 'DRY RUN — ' : '')."{$rows->count()} TTD di sistem lama: {$hasil['disalin']} disalin, {$hasil['sudah_ada']} sudah ada, {$hasil['tidak_cocok']} tanpa asesor, {$hasil['file_hilang']} file hilang.");

        return self::SUCCESS;
    }
}
