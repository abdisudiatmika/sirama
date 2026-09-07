<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidasiCpmk extends Model
{
    protected $table = 'validasi_cpmk';

    protected $fillable = [
        'pengajuan_mk_id',
        'asesor_id',
        'cpmk_id',
        'valid',
        'asli',
        'terkini',
        'memadai',
    ];

    protected function casts(): array
    {
        return [
            'valid' => 'boolean',
            'asli' => 'boolean',
            'terkini' => 'boolean',
            'memadai' => 'boolean',
        ];
    }

    public function pengajuanMk(): BelongsTo
    {
        return $this->belongsTo(PengajuanMk::class);
    }

    public function asesor(): BelongsTo
    {
        return $this->belongsTo(Asesor::class);
    }

    public function cpmk(): BelongsTo
    {
        return $this->belongsTo(Cpmk::class);
    }
}
