<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DokumenCv extends Model
{
    protected $table = 'dokumen_cv';

    protected $fillable = [
        'mahasiswa_id',
        'url',
        'keterangan',
    ];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
