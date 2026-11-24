<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Asesor extends Model
{
    protected $table = 'asesor';

    protected $fillable = [
        'user_id',
        'nidn',
        'bidang_keahlian',
        'no_hp',
        'tanda_tangan',
        'is_aktif',
    ];

    protected function casts(): array
    {
        return [
            'is_aktif' => 'boolean',
        ];
    }

    /** Absolute path of the signature image, or null when none is on disk. */
    public function pathTandaTangan(): ?string
    {
        return $this->tanda_tangan && Storage::disk('local')->exists($this->tanda_tangan)
            ? Storage::disk('local')->path($this->tanda_tangan)
            : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function penugasan(): HasMany
    {
        return $this->hasMany(PenugasanAsesor::class);
    }

    public function penilaian(): HasMany
    {
        return $this->hasMany(Penilaian::class);
    }

    public function validasiCpmk(): HasMany
    {
        return $this->hasMany(ValidasiCpmk::class);
    }
}
