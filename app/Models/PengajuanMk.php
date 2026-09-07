<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PengajuanMk extends Model
{
    protected $table = 'pengajuan_mk';

    protected $fillable = [
        'mahasiswa_id',
        'matakuliah_id',
        'nilai_mandiri_angka',
        'nilai_mandiri_huruf',
    ];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function matakuliah(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class);
    }

    public function bukti(): BelongsToMany
    {
        return $this->belongsToMany(Bukti::class, 'pengajuan_bukti');
    }

    public function selfAssessment(): HasMany
    {
        return $this->hasMany(SelfAssessment::class);
    }

    public function mkAsal(): HasMany
    {
        return $this->hasMany(MkAsal::class);
    }

    public function penilaian(): HasMany
    {
        return $this->hasMany(Penilaian::class);
    }

    public function validasiCpmk(): HasMany
    {
        return $this->hasMany(ValidasiCpmk::class);
    }

    /**
     * Which evidence categories are attached — drives F-ASR-07's conditional
     * scoring form(s) and whether Form 6 (mk_asal) applies (F-MHS-26).
     */
    public function jenisPenilaianTersedia(): array
    {
        $kategori = $this->bukti->pluck('kategori')->unique();
        $jenis = [];

        if ($kategori->contains('formal')) {
            $jenis[] = 'formal';
        }

        if ($kategori->contains('informal')) {
            $jenis[] = 'informal';
        }

        return $jenis;
    }

    /**
     * §6.3 — a matakuliah counts as done for one asesor once every jenis
     * form that applies to it (1 or 2, per jenisPenilaianTersedia) has been
     * saved by that asesor.
     */
    public function selesaiDinilaiOleh(int $asesorId): bool
    {
        $jenisTersedia = $this->jenisPenilaianTersedia();

        if (empty($jenisTersedia)) {
            return false;
        }

        $jenisTersimpan = $this->penilaian->where('asesor_id', $asesorId)->pluck('jenis')->unique();

        return collect($jenisTersedia)->diff($jenisTersimpan)->isEmpty();
    }
}
