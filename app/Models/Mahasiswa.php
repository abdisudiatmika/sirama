<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Mahasiswa extends Model
{
    protected $table = 'mahasiswa';

    protected $fillable = [
        'user_id',
        'prodi_id',
        'skema_id',
        'nim',
        'tahun_masuk',
        'status_berkas',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prodi(): BelongsTo
    {
        return $this->belongsTo(Prodi::class);
    }

    public function skema(): BelongsTo
    {
        return $this->belongsTo(Skema::class);
    }

    public function penugasanAsesor(): HasMany
    {
        return $this->hasMany(PenugasanAsesor::class)->orderBy('urutan');
    }

    public function biodata(): HasOne
    {
        return $this->hasOne(Biodata::class);
    }

    public function pendidikanTerakhir(): HasOne
    {
        return $this->hasOne(PendidikanTerakhir::class);
    }

    public function bukti(): HasMany
    {
        return $this->hasMany(Bukti::class);
    }

    public function dokumenCv(): HasOne
    {
        return $this->hasOne(DokumenCv::class);
    }

    public function pernyataan(): HasOne
    {
        return $this->hasOne(Pernyataan::class);
    }

    public function pengajuanMk(): HasMany
    {
        return $this->hasMany(PengajuanMk::class);
    }

    public function hasilPleno(): HasMany
    {
        return $this->hasMany(HasilPleno::class);
    }

    public function lampiranLama(): HasMany
    {
        return $this->hasMany(LampiranLama::class);
    }

    public function pengembalianBerkas(): HasMany
    {
        return $this->hasMany(PengembalianBerkas::class)->latest();
    }

    /** @var Collection<int,int>|false|null memo for idMkDirekognisi() */
    private Collection|false|null $idMkDirekognisiMemo = false;

    /**
     * Course ids the student's scheme lets them have recognised, or null when
     * the student has no scheme (then every submitted course counts).
     */
    public function idMkDirekognisi(): ?Collection
    {
        if ($this->idMkDirekognisiMemo === false) {
            $this->idMkDirekognisiMemo = $this->skema_id
                ? $this->skema()->first()?->matakuliahDirekognisi()->pluck('matakuliah.id')
                : null;
        }

        return $this->idMkDirekognisiMemo;
    }

    /**
     * Submitted courses that count: those the scheme can recognise. A course
     * outside it (submitted before the scheme was narrowed) stays on record but
     * is not assessed, exported or counted towards progress.
     *
     * @return Collection<int, PengajuanMk>
     */
    public function pengajuanDirekognisi(): Collection
    {
        $ids = $this->idMkDirekognisi();

        return $ids === null ? $this->pengajuanMk : $this->pengajuanMk->filter(fn ($p) => $ids->contains($p->matakuliah_id))->values();
    }

    public function mkDirekognisi(int $matakuliahId): bool
    {
        $ids = $this->idMkDirekognisi();

        return $ids === null || $ids->contains($matakuliahId);
    }
}
