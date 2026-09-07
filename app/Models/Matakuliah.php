<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Matakuliah extends Model
{
    protected $table = 'matakuliah';

    protected $fillable = [
        'prodi_id',
        'kode',
        'nama',
        'sks',
        'semester',
        'skor_minimum',
        'dapat_direkognisi',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'dapat_direkognisi' => 'boolean',
            'is_aktif' => 'boolean',
        ];
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function cpmk(): HasMany
    {
        return $this->hasMany(Cpmk::class)->orderBy('urutan');
    }

    public function skema(): BelongsToMany
    {
        return $this->belongsToMany(Skema::class, 'skema_matakuliah');
    }

    /**
     * Display name with the recognition marker used on official recap documents.
     */
    public function getNamaTampilanAttribute(): string
    {
        return $this->nama.($this->dapat_direkognisi ? ' *' : '');
    }
}
