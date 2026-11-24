<?php

namespace App\Services;

use App\Models\Mahasiswa;
use Illuminate\Support\Str;

/**
 * Builds the §6.5 recap rows for one mahasiswa — shared by the Livewire
 * page, the PDF export, and the Excel export, so the three can never
 * silently drift apart on what a row or the average actually shows.
 */
class RekapitulasiService
{
    public function __construct(private PenilaianService $penilaianService) {}

    /** Form 9 lists every course in the skema; Form 10 only the ones the student submitted. */
    public function nomorForm(bool $hanyaDiajukan): int
    {
        return $hanyaDiajukan ? 10 : 9;
    }

    public function namaFile(Mahasiswa $mahasiswa, string $ekstensi, bool $hanyaDiajukan = false): string
    {
        return 'Form'.$this->nomorForm($hanyaDiajukan).'-'.Str::slug($mahasiswa->user->name).'-'.$mahasiswa->nim.'.'.$ekstensi;
    }

    public function untuk(Mahasiswa $mahasiswa, bool $hanyaDiajukan = false): array
    {
        $mahasiswa->loadMissing([
            // Official catalog order, as on the printed forms (imported courses have no semester; ids follow the catalog).
            'user', 'prodi', 'skema.matakuliah' => fn ($q) => $q->orderByRaw('semester is null')->orderBy('semester')->orderBy('matakuliah.id'),
            'skema.matakuliah.cpmk',
            'biodata', 'pendidikanTerakhir',
            'penugasanAsesor.asesor.user',
            'pengajuanMk.penilaian',
            'hasilPleno',
        ]);

        $asesorUrutan = $mahasiswa->penugasanAsesor->sortBy('urutan')->values();

        $baris = $mahasiswa->skema?->matakuliah->map(function ($mk) use ($mahasiswa, $asesorUrutan) {
            // Courses the scheme does not recognise (must be taken in class) still list on the forms,
            // always as not recognised and without scores, even if the student once submitted them.
            $direkognisi = (bool) ($mk->pivot->direkognisi ?? true);
            $pengajuan = $direkognisi ? $mahasiswa->pengajuanMk->firstWhere('matakuliah_id', $mk->id) : null;

            $nilaiPerAsesor = $asesorUrutan->map(function ($penugasan) use ($pengajuan) {
                if (! $pengajuan) {
                    return null;
                }

                return $pengajuan->penilaian->firstWhere('asesor_id', $penugasan->asesor_id)?->nilai_akhir;
            });

            $rataRata = $pengajuan
                ? $this->penilaianService->rataRataAsesmen($pengajuan)
                : ['rata_rata' => null, 'jumlah_penilai' => 0];

            $hasilPleno = $mahasiswa->hasilPleno->firstWhere('matakuliah_id', $mk->id);

            $usulan = null;
            if ($rataRata['rata_rata'] !== null) {
                $usulan = $rataRata['rata_rata'] >= $mk->skor_minimum ? 'Direkognisi' : 'Tidak Direkognisi';
            }

            return (object) [
                'matakuliah' => $mk,
                'pengajuan' => $pengajuan,
                'nilai_per_asesor' => $nilaiPerAsesor,
                'rata_rata' => $rataRata['rata_rata'],
                'jumlah_penilai' => $rataRata['jumlah_penilai'],
                'total_asesor' => $asesorUrutan->count(),
                'usulan' => $usulan,
                'direkognisi' => $direkognisi,
                // No evidence submitted, or outside the scheme: not recognised.
                'status' => $direkognisi && $pengajuan ? $hasilPleno?->status : 'Tidak Direkognisi',
                // The SK's letter wins over the computed one (only for recognised courses).
                'huruf' => $this->penilaianService->hurufAkhir(
                    $rataRata['rata_rata'],
                    $hasilPleno?->status === 'Direkognisi' ? $hasilPleno->nilai_huruf : null,
                )['huruf'] ?? null,
            ];
        }) ?? collect();

        if ($hanyaDiajukan) {
            $baris = $baris->filter(fn ($r) => $r->direkognisi && $r->pengajuan !== null)->values();
        }

        return [
            'mahasiswa' => $mahasiswa,
            'asesorUrutan' => $asesorUrutan,
            'baris' => $baris,
            'jenjangLabel' => $this->jenjangSebelumnya($mahasiswa),
            'nomorForm' => $this->nomorForm($hanyaDiajukan),
            'hanyaDiajukan' => $hanyaDiajukan,
        ];
    }

    public function jenjangSebelumnya(Mahasiswa $mahasiswa): string
    {
        $pendidikan = $mahasiswa->pendidikanTerakhir;

        if (! $pendidikan) {
            return '—';
        }

        $institusi = filled($pendidikan->nama_pt) ? $pendidikan->nama_pt : $pendidikan->nama_sekolah;

        return trim(($pendidikan->program ? $pendidikan->program.' ' : '').$institusi);
    }
}
