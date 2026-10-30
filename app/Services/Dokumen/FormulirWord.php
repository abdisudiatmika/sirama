<?php

namespace App\Services\Dokumen;

use App\Models\Mahasiswa;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\Element\Section;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\SimpleType\Jc;

/**
 * Shared page setup for the official RPL forms, so every generated form
 * uses the same font, margins and file naming. Subclasses only write content.
 *
 * A form can come in several variants (e.g. one per assessor); the variant
 * is a small integer chosen from daftarVarian() and read by the subclass.
 */
abstract class FormulirWord
{
    /** Usable text width in twips for the A4 margins below. */
    protected const LEBAR_HALAMAN = 9300;

    /** Usable text width in twips on a landscape A4 page with 2 cm margins. */
    protected const LEBAR_HALAMAN_LANSKAP = 14500;

    protected ?int $varian = null;

    protected function lanskap(): bool
    {
        return false;
    }

    abstract public function nomor(): int;

    abstract protected function isi(Section $section, Mahasiswa $mahasiswa): void;

    /** @return list<array{varian: ?int, label: string}> */
    public function daftarVarian(Mahasiswa $mahasiswa): array
    {
        return [['varian' => null, 'label' => 'Word']];
    }

    public function denganVarian(?int $varian): static
    {
        $salinan = clone $this;
        $salinan->varian = $varian;

        return $salinan;
    }

    protected function labelVarian(): ?string
    {
        return null;
    }

    protected function namaJenjang(?string $jenjang): string
    {
        return match ($jenjang) {
            'D3' => 'Diploma Tiga',
            'D4' => 'Sarjana Terapan',
            default => (string) $jenjang,
        };
    }

    public function namaFile(Mahasiswa $mahasiswa): string
    {
        $varian = $this->labelVarian() ? '-'.Str::slug($this->labelVarian()) : '';

        return 'Form'.$this->nomor().$varian.'-'.Str::slug($mahasiswa->user->name).'-'.$mahasiswa->nim.'.docx';
    }

    public function simpan(Mahasiswa $mahasiswa, string $path): void
    {
        // Without this, a "&" or "<" in user-entered data produces a corrupt .docx.
        Settings::setOutputEscapingEnabled(true);

        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(12);

        $section = $word->addSection($this->lanskap()
            ? ['orientation' => 'landscape', 'marginTop' => 1134, 'marginBottom' => 1134, 'marginLeft' => 1134, 'marginRight' => 1134]
            : ['marginTop' => 1134, 'marginBottom' => 1134, 'marginLeft' => 1418, 'marginRight' => 1134]);
        $section->addText('FORM '.$this->nomor(), ['bold' => true], ['alignment' => Jc::END, 'spaceAfter' => 200]);

        $this->isi($section, $mahasiswa);

        IOFactory::createWriter($word, 'Word2007')->save($path);
    }
}
