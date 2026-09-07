<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Mahasiswa extends Model
{
    protected $table = 'mahasiswa';

    protected $fillable = [
        'user_id',
        'prodi_id',
        'skema_id',
        'nim',
        'tahun_masuk',
        'status_berkas',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function skema(): BelongsTo
    {
        return $this->belongsTo(Skema::class);
    }

    public function penugasanAsesor(): HasMany
    {
        return $this->hasMany(PenugasanAsesor::class)->orderBy('urutan');
    }

    public function biodata(): HasOne
    {
        return $this->hasOne(Biodata::class);
    }

    public function pendidikanTerakhir(): HasOne
    {
        return $this->hasOne(PendidikanTerakhir::class);
    }

    public function bukti(): HasMany
    {
        return $this->hasMany(Bukti::class);
    }

    public function dokumenCv(): HasOne
    {
        return $this->hasOne(DokumenCv::class);
    }

    public function pernyataan(): HasOne
    {
        return $this->hasOne(Pernyataan::class);
    }

    public function pengajuanMk(): HasMany
    {
        return $this->hasMany(PengajuanMk::class);
    }

    public function hasilPleno(): HasMany
    {
        return $this->hasMany(HasilPleno::class);
    }

    public function pengembalianBerkas(): HasMany
    {
        return $this->hasMany(PengembalianBerkas::class)->latest();
    }
}
