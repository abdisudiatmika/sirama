<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MkAsal extends Model
{
    protected $table = 'mk_asal';

    protected $fillable = [
        'pengajuan_mk_id',
        'kode_asal',
        'nama_mk_asal',
        'cpmk_asal',
    ];

    public function pengajuanMk(): BelongsTo
    {
        return $this->belongsTo(PengajuanMk::class);
    }
}
