<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PendidikanTerakhir extends Model
{
    protected $table = 'pendidikan_terakhir';

    protected $fillable = [
        'mahasiswa_id',
        'nama_sekolah',
        'alamat_sekolah',
        'tahun_lulus_sekolah',
        'nama_pt',
        'jurusan_prodi',
        'program',
        'tahun_lulus_pt',
    ];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
