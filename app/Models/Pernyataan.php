<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pernyataan extends Model
{
    protected $table = 'pernyataan';

    protected $fillable = [
        'mahasiswa_id',
        'disetujui',
        'tempat',
        'tanggal',
        'waktu_setuju',
        'ip_address',
    ];

    protected function casts(): array
    {
        return [
            'disetujui' => 'boolean',
            'tanggal' => 'date',
            'waktu_setuju' => 'datetime',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
