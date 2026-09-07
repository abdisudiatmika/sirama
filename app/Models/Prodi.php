<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Prodi extends Model
{
    protected $table = 'prodi';

    protected $fillable = [
        'kode',
        'nama',
        'jenjang',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'admin_prodi_prodi');
    }

    public function matakuliah(): HasMany
    {
        return $this->hasMany(Matakuliah::class);
    }

    public function skema(): HasMany
    {
        return $this->hasMany(Skema::class);
    }

    public function mahasiswa(): HasMany
    {
        return $this->hasMany(Mahasiswa::class);
    }
}
