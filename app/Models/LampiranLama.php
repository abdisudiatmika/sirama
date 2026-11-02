<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A file a student uploaded in the legacy sirama system, kept read-only for download. */
class LampiranLama extends Model
{
    protected $table = 'lampiran_lama';

    protected $fillable = [
        'mahasiswa_id',
        'legacy_id',
        'label',
        'nama_file',
        'path',
        'mime_type',
        'ukuran',
    ];

    public const LABEL = [
        'ijazah' => 'Ijazah',
        'transkrip' => 'Transkrip',
        'pengalaman' => 'Pengalaman Kerja',
        'sertifikat_pelatihan' => 'Sertifikat Pelatihan',
        'sertifikat_kompetensi' => 'Sertifikat Kompetensi',
        'video' => 'Video',
        'cv' => 'CV',
        'pernyataan' => 'Pernyataan',
    ];

    public function mahasiswa(): BelongsTo
    {
        return $this->belongsTo(Mahasiswa::class);
    }

    public function namaLabel(): string
    {
        return self::LABEL[$this->label] ?? 'Lainnya';
    }
}
