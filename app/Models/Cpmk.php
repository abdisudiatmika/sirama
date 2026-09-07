<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cpmk extends Model
{
    protected $table = 'cpmk';

    protected $fillable = [
        'matakuliah_id',
        'kode',
        'deskripsi',
        'urutan',
    ];

    public function matakuliah(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class);
    }
}
