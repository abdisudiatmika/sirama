<?php

namespace App\Console\Commands;

use App\Models\HasilPleno;
use App\Models\Mahasiswa;
use App\Models\PengajuanMk;
use App\Models\Skema;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Marks which courses of a scheme can be recognised, from the prodi's sheet
 * (columns: NO, KODE MK, NAMA MK, SKS, WAJIB/TIDAK WAJIB). "WAJIB" courses
 * must be taken in class; "TIDAK WAJIB/RPL" ones can be recognised. Every
 * course stays in the scheme so the forms still list all of them.
 */
class ImportMkSkema extends Command
{
    protected $signature = 'sirama:import-mk-skema
        {file : file Excel berisi sheet MK per skema}
        {sheet : nama sheet, mis. "MK REG"}
        {skema : id atau nama persis skema di sistem}
        {--dry-run : jalankan lalu batalkan semua perubahan}';

    protected $description = 'Tandai MK skema yang bisa direkognisi (TIDAK WAJIB/RPL) dan yang wajib kuliah (WAJIB) dari sheet Excel prodi';

    public function handle(): int
    {
        $skema = is_numeric($this->argument('skema'))
            ? Skema::find((int) $this->argument('skema'))
            : Skema::where('nama', $this->argument('skema'))->first();
        if (! $skema) {
            $this->error('Skema tidak ditemukan: '.$this->argument('skema'));

            return self::FAILURE;
        }

        try {
            $sheet = IOFactory::load($this->argument('file'))->getSheetByName($this->argument('sheet'));
        } catch (\Throwable $e) {
            $this->error('File tidak bisa dibaca: '.$e->getMessage());

            return self::FAILURE;
        }
        if (! $sheet) {
            $this->error('Sheet "'.$this->argument('sheet').'" tidak ada di file.');

            return self::FAILURE;
        }

        $status = [];
        for ($r = 2; $r <= $sheet->getHighestRow(); $r++) {
            $kode = trim((string) $sheet->getCell("B{$r}")->getValue());
            $ket = mb_strtoupper(trim((string) $sheet->getCell("E{$r}")->getValue()));
            if ($kode === '' || $ket === '') {
                continue;
            }
            $status[$kode] = ! str_starts_with($ket, 'WAJIB');
        }

        $mk = $skema->matakuliah()->get()->keyBy(fn ($m) => trim($m->kode));
        $tidakDiSkema = array_diff(array_keys($status), $mk->keys()->all());
        $tidakDiSheet = $mk->keys()->diff(array_keys($status));

        $this->line("Skema     : {$skema->nama} ({$mk->count()} MK)");
        $this->line('Sheet     : '.count($status).' MK, '.count(array_filter($status)).' direkognisi, '.count(array_filter($status, fn ($v) => ! $v)).' wajib kuliah');
        foreach ($tidakDiSkema as $kode) {
            $this->warn("  ! {$kode} ada di sheet tetapi bukan anggota skema, dilewati.");
        }
        foreach ($tidakDiSheet as $kode) {
            $this->warn("  ! {$kode} ({$mk[$kode]->nama}) anggota skema tetapi tidak ada di sheet, statusnya tidak diubah.");
        }

        DB::beginTransaction();
        $berubah = 0;
        foreach ($status as $kode => $direkognisi) {
            if ($m = $mk->get($kode)) {
                $berubah += (bool) $m->pivot->direkognisi !== $direkognisi ? 1 : 0;
                $skema->matakuliah()->updateExistingPivot($m->id, ['direkognisi' => $direkognisi]);
            }
        }

        // What the change touches: submissions and pleno results on courses now marked wajib.
        $wajibIds = $mk->filter(fn ($m, $kode) => ($status[$kode] ?? true) === false)->pluck('id');
        $mahasiswaIds = Mahasiswa::where('skema_id', $skema->id)->pluck('id');
        $pengajuanWajib = PengajuanMk::whereIn('mahasiswa_id', $mahasiswaIds)->whereIn('matakuliah_id', $wajibIds)->count();
        $plenoDiakui = HasilPleno::whereIn('mahasiswa_id', $mahasiswaIds)->whereIn('matakuliah_id', $wajibIds)->where('status', 'Direkognisi')
            ->with(['mahasiswa.user', 'matakuliah'])->get();

        $this->line("Diubah    : {$berubah} MK");
        $this->line("Pengajuan MK wajib yang sudah ada: {$pengajuanWajib} (tetap tersimpan, tidak lagi dinilai atau dicetak sebagai diajukan)");
        if ($plenoDiakui->isNotEmpty()) {
            $this->warn("  ! {$plenoDiakui->count()} hasil pleno/SK berstatus Direkognisi pada MK wajib (cek dengan prodi):");
            foreach ($plenoDiakui as $h) {
                $this->warn("    - {$h->mahasiswa->user->name} ({$h->mahasiswa->nim}): {$h->matakuliah->kode} {$h->matakuliah->nama}");
            }
        }

        if ($this->option('dry-run')) {
            DB::rollBack();
            $this->warn('DRY RUN — semua perubahan dibatalkan.');
        } else {
            DB::commit();
            $this->info('Tanda MK skema tersimpan.');
        }

        return self::SUCCESS;
    }
}
