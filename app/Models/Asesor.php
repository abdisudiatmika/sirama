<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asesor extends Model
{
    protected $table = 'asesor';

    protected $fillable = [
        'user_id',
        'nidn',
        'bidang_keahlian',
        'no_hp',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(PenugasanAsesor::class);
    }
}
