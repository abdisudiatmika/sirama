<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Bukti extends Model
{
    protected $table = 'bukti';

    protected $fillable = [
        'mahasiswa_id',
        'kategori',
        'jenis_bukti',
        'judul',
        'penerbit',
        'tahun',
        'url',
        'keterangan',
    ];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function pengajuanMk(): BelongsToMany
    {
        return $this->belongsToMany(PengajuanMk::class, 'pengajuan_bukti');
    }
}
