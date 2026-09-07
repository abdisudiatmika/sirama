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
        ['min' => 80, 'max' => 100, 'huruf' => 'A', 'bobot' => 4.00],
        ['min' => 71, 'max' => 79, 'huruf' => 'AB', 'bobot' => 3.50],
        ['min' => 65, 'max' => 70, 'huruf' => 'B', 'bobot' => 3.00],
        ['min' => 60, 'max' => 64, 'huruf' => 'BC', 'bobot' => 2.50],
        ['min' => 55, 'max' => 59, 'huruf' => 'C', 'bobot' => 2.00],
        ['min' => 40, 'max' => 54, 'huruf' => 'D', 'bobot' => 1.00],
        ['min' => 0, 'max' => 39, 'huruf' => 'E', 'bobot' => 0.00],
    ],

];
