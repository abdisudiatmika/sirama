<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SelfAssessment extends Model
{
    protected $table = 'self_assessment';

    protected $fillable = [
        'pengajuan_mk_id',
        'cpmk_id',
        'status',
    ];

    public function pengajuanMk(): BelongsTo
    {
        return $this->belongsTo(PengajuanMk::class);
    }

    public function cpmk(): BelongsTo
    {
        return $this->belongsTo(Cpmk::class);
    }
}
