<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Penilaian extends Model
{
    protected $table = 'penilaian';

    protected $fillable = [
        'pengajuan_mk_id',
        'asesor_id',
        'jenis',
        'nilai',
        'catatan_reviewer',
        'catatan_kesenjangan',
        'nilai_akhir',
    ];

    public function pengajuanMk(): BelongsTo
    {
        return $this->belongsTo(PengajuanMk::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(Asesor::class);
    }
}
