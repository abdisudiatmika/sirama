<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Biodata extends Model
{
    protected $table = 'biodata';

    protected $fillable = [
        'mahasiswa_id',
        'nama_lengkap',
        'tempat_lahir',
        'tanggal_lahir',
        'jenis_kelamin',
        'status',
        'kebangsaan',
        'alamat_rumah',
        'kode_pos',
        'telp_rumah_hp',
        'alamat_kantor',
        'telp_kantor',
        'email',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_lahir' => 'date',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }
}
