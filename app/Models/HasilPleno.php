<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HasilPleno extends Model
{
    protected $table = 'hasil_pleno';

    protected $fillable = [
        'mahasiswa_id',
        'matakuliah_id',
        'status',
        'catatan_pleno',
        'ditetapkan_oleh',
        'tanggal_pleno',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_pleno' => 'date',
        ];
    }

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function matakuliah(): BelongsTo
    {
        return $this->belongsTo(Matakuliah::class);
    }

    public function penetap(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ditetapkan_oleh');
    }
}
