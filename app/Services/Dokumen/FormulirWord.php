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
 */
abstract class FormulirWord
{
    /** Usable text width in twips for the A4 margins below. */
    protected const LEBAR_HALAMAN = 9300;

    abstract public function nomor(): int;

    abstract protected function isi(Section $section, Mahasiswa $mahasiswa): void;

    public function namaFile(Mahasiswa $mahasiswa): string
    {
        return 'Form'.$this->nomor().'-'.Str::slug($mahasiswa->user->name).'-'.$mahasiswa->nim.'.docx';
    }

    public function simpan(Mahasiswa $mahasiswa, string $path): void
    {
        // Without this, a "&" or "<" in user-entered data produces a corrupt .docx.
        Settings::setOutputEscapingEnabled(true);

        $word = new PhpWord;
        $word->setDefaultFontName('Times New Roman');
        $word->setDefaultFontSize(12);

        $section = $word->addSection(['marginTop' => 1134, 'marginBottom' => 1134, 'marginLeft' => 1418, 'marginRight' => 1134]);
        $section->addText('FORM '.$this->nomor(), ['bold' => true], ['alignment' => Jc::END, 'spaceAfter' => 200]);

        $this->isi($section, $mahasiswa);

        IOFactory::createWriter($word, 'Word2007')->save($path);
    }
}
