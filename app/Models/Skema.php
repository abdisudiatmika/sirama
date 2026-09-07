<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Skema extends Model
{
    protected $table = 'skema';

    protected $fillable = [
        'prodi_id',
        'nama',
        'jenis',
        'tahun_akademik',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function matakuliah(): BelongsToMany
    {
        return $this->belongsToMany(Matakuliah::class, 'skema_matakuliah');
    }

    public function mahasiswa(): HasMany
    {
        return $this->hasMany(Mahasiswa::class);
    }

    public function getTotalSksAttribute(): int
    {
        return $this->matakuliah->sum('sks');
    }
}
