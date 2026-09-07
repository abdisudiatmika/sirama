<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PengembalianBerkas extends Model
{
    protected $table = 'pengembalian_berkas';

    protected $fillable = [
        'mahasiswa_id',
        'alasan',
        'dikembalikan_oleh',
    ];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'dikembalikan_oleh');
    }
}
