<?php

namespace App\Support;

/** Scores carry two decimals; whole scores print as "85", others as on the SK ("83.90"). */
class Nilai
{
    public static function format(int|float|string|null $nilai): ?string
    {
        if ($nilai === null || $nilai === '') {
            return null;
        }

        $nilai = round((float) $nilai, 2);

        return fmod($nilai, 1.0) === 0.0 ? (string) (int) $nilai : number_format($nilai, 2, '.', '');
    }
}
