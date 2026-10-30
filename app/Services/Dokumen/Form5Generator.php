<?php

namespace App\Services\Dokumen;

use App\Models\Mahasiswa;
use App\Models\PengajuanMk;
use App\Models\PenugasanAsesor;
use Illuminate\Support\Collection;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\Element\Table as TabelWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Table;

/**
 * Asesmen Mandiri. The student's Ya/Tidak and evidence are the same in every
 * copy, but each assessor validates (VATM) independently and signs their own
 * copy, so there is one variant per assigned assessor (variant = urutan).
 */
class Form5Generator extends FormulirWord
{
    private const LEBAR = ['no' => 500, 'indikator' => 3350, 'ya' => 550, 'tidak' => 650, 'bukti' => 2250, 'vatm' => 500];

    private const CENTANG = '√';

    private const CONTOH_BUKTI = [
        'Daftar Riwayat Hidup;',
        'Sertifikat keahlian yang dikeluarkan oleh Asosiasi Profesi/LSP/Lembaga Pelatihan lainnya yang diakui secara nasional atau internasional;',
        'Sertifikat kursus/pelatihan yang dikeluarkan oleh perguruan tinggi;',
        'Sertifikat kursus/pelatihan yang dikeluarkan oleh industri/pabrik/perusahaan;',
        'Sertifikat mengikuti workshops, seminar, simposium, dan lain-lain;',
        'Penghargaan dari industri atau lembaga lainnya yang kredibel;',
        'Keanggotaan dalam asosiasi profesi;',
        'Karya monumental atau karya ilmiah yang dipublikasikan;',
        'Laporan tertulis dari penyelia/supervisor/ pembimbing;',
        'Catatan harian aktivitas pekerjaan (log book);',
        'Contoh laporan atau dokumen yang dibuat oleh calon peserta;',
        'Formulir asesmen mandiri yang telah diisi; dan',
        'Dokumen lainnya yang membuktikan bahwa calon peserta telah memiliki pengalaman/keahlian/pengetahuan tertentu yang relevan dan cukup terhadap standar capaian pembelajaran/kompetensi yang dilamar.',
    ];

    private const ROMAWI = [1 => 'I', 2 => 'II', 3 => 'III'];

    public function nomor(): int
    {
        return 5;
    }

    public function daftarVarian(Mahasiswa $mahasiswa): array
    {
        $penugasan = $mahasiswa->penugasanAsesor()->orderBy('urutan')->get();

        if ($penugasan->isEmpty()) {
            return parent::daftarVarian($mahasiswa);
        }

        return $penugasan->map(fn (PenugasanAsesor $p) => ['varian' => $p->urutan, 'label' => 'Asesor '.$p->urutan])->all();
    }

    protected function labelVarian(): ?string
    {
        return $this->varian ? 'Asesor '.$this->varian : null;
    }

    protected function isi(Section $section, Mahasiswa $mahasiswa): void
    {
        $mahasiswa->loadMissing(['user', 'prodi', 'biodata']);
        $penugasan = $this->varian
            ? $mahasiswa->penugasanAsesor()->with('asesor.user')->where('urutan', $this->varian)->first()
            : null;

        $section->addText('FORMULIR ASESMEN MANDIRI', ['bold' => true, 'size' => 13], ['alignment' => Jc::CENTER, 'spaceAfter' => 240]);

        $identitas = $this->tabelPolos($section);
        foreach ([
            ['Nama Calon Peserta', $mahasiswa->biodata->nama_lengkap ?? $mahasiswa->user->name],
            ['Jenis kelamin', $mahasiswa->biodata?->jenis_kelamin],
            ['Program Studi', mb_strtoupper(trim($this->namaJenjang($mahasiswa->prodi->jenjang).' '.$mahasiswa->prodi->nama))],
        ] as [$label, $nilai]) {
            $identitas->addRow();
            $identitas->addCell(2600)->addText($label, [], ['spaceAfter' => 0]);
            $identitas->addCell(self::LEBAR_HALAMAN - 2600)->addText(': '.($nilai ?? ''), [], ['spaceAfter' => 0]);
        }

        $paragraf = ['alignment' => Jc::BOTH, 'spaceBefore' => 160, 'spaceAfter' => 0];
        $section->addText('Tujuan dari asesmen mandiri ini adalah untuk mengidentifikasi keterampilan dan pengetahuan yang sudah saudara miliki dan kesiapan saudara untuk dinilai dan dapat direkognisi.', [], $paragraf);
        $section->addText('Untuk mengisi formulir ini, jawablah semua pertanyaan asesmen mandiri berikut dengan memberi tanda “√” di kolom (Ya/Tidak) yang paling mencerminkan tingkat keterampilan dan pengetahuan saudara saat ini. Ketika saudara menjawab setiap pertanyaan, buat catatan tentang jenis bukti yang saudara miliki yang dapat digunakan untuk memverifikasi keterampilan dan pengetahuan saudara. Jika saudara mengklaim mampu dan telah memiliki keterampilan dan pengetahuan yang tertera dalam pertanyaan, maka saudara harus dapat memberikan bukti sebagaimana contoh ini.', [], $paragraf);
        $section->addText('Contoh jenis bukti yang dapat saudara berikan meliputi:', [], $paragraf + ['spaceAfter' => 60]);
        foreach (self::CONTOH_BUKTI as $i => $contoh) {
            $section->addText(($i + 1).'. '.$contoh, [], ['alignment' => Jc::BOTH, 'spaceAfter' => 0, 'indentation' => ['left' => 400, 'hanging' => 300]]);
        }

        $pengajuan = $this->pengajuan($mahasiswa);

        if ($pengajuan->isEmpty()) {
            $section->addText('Mahasiswa belum mengajukan mata kuliah.', ['italic' => true], ['spaceBefore' => 280]);
        }

        foreach ($pengajuan as $p) {
            $this->tabelMatakuliah($section, $p, $penugasan?->asesor_id);
        }

        $this->blokTandaTangan($section, $mahasiswa, $penugasan);

        $keterangan = ['size' => 10];
        $section->addText('V: Valid, yaitu terdapat hubungan yang jelas antara bukti yang diperlukan dengan indikator capaian pembelajaran yang akan dinilai;', $keterangan, ['spaceBefore' => 200, 'spaceAfter' => 0]);
        $section->addText('A: Asli/Otentik, bukti yang disampaikan dapat diverifikasi di tempat kerja Calon Peserta atau di tempat lainnya yang relevan dengan bukti tersebut;', $keterangan, ['spaceAfter' => 0]);
        $section->addText('T: Terkini, bukti yang disampaikan mendemonstrasikan pengetahuan dan ketrampilan terkini yang dimiliki calon pada saat melamar;', $keterangan, ['spaceAfter' => 0]);
        $section->addText('M: Mencukupi, bukti yang disampaikan harus menunjukkan kinerja indikator capaian pembelajaran yang cukup untuk dinilai;', $keterangan, ['spaceAfter' => 0]);
    }

    /** @return Collection<int, PengajuanMk> */
    private function pengajuan(Mahasiswa $mahasiswa): Collection
    {
        // Same catalog order as Form 2 (imported courses have no semester, ids follow the official order).
        return $mahasiswa->pengajuanMk()
            ->join('matakuliah', 'matakuliah.id', '=', 'pengajuan_mk.matakuliah_id')
            ->orderByRaw('matakuliah.semester is null')->orderBy('matakuliah.semester')->orderBy('matakuliah.id')
            ->select('pengajuan_mk.*')
            ->with([
                'matakuliah.cpmk' => fn ($q) => $q->orderBy('urutan')->orderBy('id'),
                'selfAssessment',
                'bukti',
                'validasiCpmk',
            ])
            ->get();
    }

    private function tabelMatakuliah(Section $section, PengajuanMk $pengajuan, ?int $asesorId): void
    {
        $section->addText('Mata Kuliah : '.$pengajuan->matakuliah->nama, ['bold' => true], ['spaceBefore' => 280, 'spaceAfter' => 80, 'keepNext' => true]);

        $l = self::LEBAR;
        $tabel = $section->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => self::LEBAR_HALAMAN,
            'unit' => TblWidth::TWIP,
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 50,
        ]);

        $abu = ['bgColor' => 'D9D9D9', 'valign' => 'center'];
        $kepala = ['bold' => true, 'size' => 10];
        $tengah = ['alignment' => Jc::CENTER, 'spaceAfter' => 0];

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($l['no'], $abu + ['vMerge' => 'restart'])->addText('No', $kepala, $tengah);
        $tabel->addCell($l['indikator'], $abu + ['vMerge' => 'restart'])->addText('Pertanyaan Indikator CP Mata Kuliah atau elemen kompetensi', $kepala, $tengah);
        $tabel->addCell($l['ya'] + $l['tidak'], $abu + ['gridSpan' => 2])->addText('Level Kompetensi', $kepala, $tengah);
        $tabel->addCell($l['bukti'], $abu + ['vMerge' => 'restart'])->addText('Bukti yang disampaikan', $kepala, $tengah);
        $tabel->addCell($l['vatm'] * 4, $abu + ['gridSpan' => 4])->addText('Diisi Asesor', $kepala, $tengah);

        $tabel->addRow(null, ['tblHeader' => true]);
        $tabel->addCell($l['no'], $abu + ['vMerge' => 'continue']);
        $tabel->addCell($l['indikator'], $abu + ['vMerge' => 'continue']);
        $tabel->addCell($l['ya'], $abu)->addText('Ya', $kepala, $tengah);
        $tabel->addCell($l['tidak'], $abu)->addText('Tidak', $kepala, $tengah);
        $tabel->addCell($l['bukti'], $abu + ['vMerge' => 'continue']);
        foreach (['V', 'A', 'T', 'M'] as $huruf) {
            $tabel->addCell($l['vatm'], $abu)->addText($huruf, $kepala, $tengah);
        }

        $cpmkList = $pengajuan->matakuliah->cpmk;
        $selfPerCpmk = $pengajuan->selfAssessment->keyBy('cpmk_id');
        $vatmPerCpmk = $asesorId ? $pengajuan->validasiCpmk->where('asesor_id', $asesorId)->keyBy('cpmk_id') : collect();

        if ($cpmkList->isEmpty()) {
            $tabel->addRow();
            $tabel->addCell(self::LEBAR_HALAMAN, ['gridSpan' => 9])->addText('Belum ada indikator CPMK untuk mata kuliah ini.', ['italic' => true, 'size' => 10], $tengah);

            return;
        }

        $isi = ['size' => 10];
        $kiri = ['spaceAfter' => 0];

        foreach ($cpmkList->values() as $i => $cpmk) {
            $status = $selfPerCpmk->get($cpmk->id)?->status;
            $vatm = $vatmPerCpmk->get($cpmk->id);

            $tabel->addRow(null, ['cantSplit' => true]);
            $tabel->addCell($l['no'])->addText((string) ($i + 1), $isi, $tengah);
            $tabel->addCell($l['indikator'])->addText($cpmk->deskripsi, $isi, $kiri);
            $tabel->addCell($l['ya'])->addText($status === 'mampu' ? self::CENTANG : '', $isi, $tengah);
            $tabel->addCell($l['tidak'])->addText($status === 'belum_mampu' ? self::CENTANG : '', $isi, $tengah);

            $selBukti = $tabel->addCell($l['bukti'], ['vMerge' => $i === 0 ? 'restart' : 'continue']);
            if ($i === 0) {
                $this->isiBukti($selBukti, $pengajuan);
            }

            foreach (['valid', 'asli', 'terkini', 'memadai'] as $kolom) {
                $tabel->addCell($l['vatm'])->addText($vatm?->{$kolom} ? self::CENTANG : '', $isi, $tengah);
            }
        }
    }

    private function isiBukti($sel, PengajuanMk $pengajuan): void
    {
        if ($pengajuan->bukti->isEmpty()) {
            $sel->addText('-', ['size' => 10], ['spaceAfter' => 0]);

            return;
        }

        foreach ($pengajuan->bukti as $i => $bukti) {
            $sel->addText(($i + 1).'. '.$bukti->jenis_bukti.($bukti->judul ? ': '.$bukti->judul : ''), ['size' => 10], ['spaceAfter' => 40]);
        }
    }

    private function blokTandaTangan(Section $section, Mahasiswa $mahasiswa, ?PenugasanAsesor $penugasan): void
    {
        $section->addTextBreak();

        $tabel = $section->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => self::LEBAR_HALAMAN,
            'unit' => TblWidth::TWIP,
            'borderSize' => 6,
            'borderColor' => '000000',
            'cellMargin' => 80,
        ]);
        $kiri = 5300;
        $kanan = self::LEBAR_HALAMAN - $kiri;
        $p = ['spaceAfter' => 60];
        $tebal = ['bold' => true];

        $tabel->addRow(null, ['cantSplit' => true]);
        $rekomendasi = $tabel->addCell($kiri);
        $rekomendasi->addText('Rekomendasi Asesor :', $tebal, $p);
        $rekomendasi->addText('☐ Asesmen dilanjutkan', [], $p);
        $rekomendasi->addText('☐ Asesmen tidak dilanjutkan', [], $p);

        $peserta = $tabel->addCell($kanan);
        $peserta->addText('Peserta :', $tebal, $p);
        $peserta->addText('Nama : '.($mahasiswa->biodata->nama_lengkap ?? $mahasiswa->user->name), [], $p);
        $peserta->addText('Tanda tangan/Tanggal :', [], ['spaceAfter' => 700]);

        $tabel->addRow(null, ['cantSplit' => true]);
        $catatan = $tabel->addCell($kiri);
        $catatan->addText('Catatan :', $tebal, $p);
        $catatan->addText('Asesi (telah/belum)* menyatakan dirinya kompeten. Jika belum menyatakan dirinya kompeten tuliskan Nomor Indikator CP Mata Kuliah yang belum kompeten: ......................................', ['size' => 10], $p + ['alignment' => Jc::BOTH]);
        $catatan->addText('Bukti yang dimiliki oleh Asesi (telah/belum)* memenuhi persyaratan asesmen sesuai dengan persyaratan CP Mata Kuliah. Tuliskan nama bukti jika belum memenuhi persyaratan asesmen sesuai dengan persyaratan Capaian Pembelajaran: ......................................', ['size' => 10], $p + ['alignment' => Jc::BOTH]);

        $urutan = $penugasan?->urutan ?? 1;
        $asesor = $tabel->addCell($kanan);
        $asesor->addText('Asesor '.(self::ROMAWI[$urutan] ?? $urutan).' :', $tebal, $p);
        $asesor->addText('Nama : '.($penugasan?->asesor?->user?->name ?? ''), [], $p);
        $asesor->addText('NIP/NIDN : '.($penugasan?->asesor?->nidn ?? ''), [], $p);
        $asesor->addText('Tanggal/Tanda tangan :', [], ['spaceAfter' => 700]);
    }

    private function namaJenjang(?string $jenjang): string
    {
        return match ($jenjang) {
            'D3' => 'Diploma Tiga',
            'D4' => 'Sarjana Terapan',
            default => (string) $jenjang,
        };
    }

    private function tabelPolos(Section $section): TabelWord
    {
        return $section->addTable([
            'layout' => Table::LAYOUT_FIXED,
            'width' => self::LEBAR_HALAMAN,
            'unit' => TblWidth::TWIP,
            'borderSize' => 0,
            'borderColor' => 'FFFFFF',
            'cellMargin' => 30,
        ]);
    }
}
