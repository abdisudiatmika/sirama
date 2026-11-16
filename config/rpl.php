<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Kebijakan Penilaian
    |--------------------------------------------------------------------------
    */

    'batas_nilai_gabungan' => 85,
    'bobot_informal' => 0.10,
    'skor_minimum_default' => 66,
    'maks_asesor_per_mahasiswa' => 3,
    'min_asesor_untuk_pleno' => 2,

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    */

    'session_idle_timeout_menit' => 60,
    'maks_percobaan_login' => 5,
    'jeda_setelah_gagal_login_menit' => 15,

    /*
    |--------------------------------------------------------------------------
    | Konversi Nilai Huruf
    |--------------------------------------------------------------------------
    */

    'konversi_huruf' => [
        // PNB grading table, as applied in the RPL result SK (A from 81, AB from 76).
        ['min' => 81, 'max' => 100, 'huruf' => 'A', 'bobot' => 4.00],
        ['min' => 76, 'max' => 80, 'huruf' => 'AB', 'bobot' => 3.50],
        ['min' => 71, 'max' => 75, 'huruf' => 'B', 'bobot' => 3.00],
        ['min' => 66, 'max' => 70, 'huruf' => 'BC', 'bobot' => 2.50],
        ['min' => 61, 'max' => 65, 'huruf' => 'C', 'bobot' => 2.00],
        ['min' => 41, 'max' => 60, 'huruf' => 'D', 'bobot' => 1.00],
        ['min' => 0, 'max' => 40, 'huruf' => 'E', 'bobot' => 0.00],
    ],

    /*
    |--------------------------------------------------------------------------
    | Identitas Institusi (kop surat cetak/export)
    |--------------------------------------------------------------------------
    */

    // Rows-per-page choices offered under every paginated table; the first is the default.
    'pilihan_baris_per_halaman' => [15, 25, 50, 100],

    /*
     * Folder holding the legacy system's "uploads/..." files (attachment.file_path
     * is relative to it). Keep it outside public/ — these are personal documents.
     */
    'lampiran_lama_path' => env('LAMPIRAN_LAMA_PATH', storage_path('app/private/lampiran-lama')),

    'institusi' => [
        'nama' => 'Politeknik Negeri Bali',
        'kementerian' => 'Kementerian Pendidikan Tinggi, Sains, dan Teknologi',
        'alamat' => 'Kampus Bukit, Jimbaran, Kuta Selatan, Badung, Bali 80364',
        'kota' => 'Badung', // tempat penandatanganan formulir
        'kontak' => 'Telp. (0361) 701981 · Faks. (0361) 701128 · www.pnb.ac.id',
        'logo_path' => null, // isi path absolut file logo bila tersedia
    ],

];
