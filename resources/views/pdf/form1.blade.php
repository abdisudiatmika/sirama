@php
    $b = $mahasiswa->biodata;
    $p = $mahasiswa->pendidikanTerakhir;
    $isi = fn ($v) => filled($v) ? $v : '';

    $tempatTgl = collect([
        $b?->tempat_lahir,
        $b?->tanggal_lahir?->locale('id')->translatedFormat('d F Y'),
    ])->filter()->implode(', ');

    $dataPribadi = [
        'Nama lengkap' => $b->nama_lengkap ?? $mahasiswa->user->name,
        'Tempat / tgl. Lahir' => $tempatTgl,
        'Jenis kelamin' => $b?->jenis_kelamin,
        'Status' => $b?->status,
        'Kebangsaan' => $b?->kebangsaan,
        'Alamat rumah' => $b?->alamat_rumah,
        'Kode pos' => $b?->kode_pos,
        'Telpon rumah/HP' => $b?->telp_rumah_hp,
        'Alamat kantor' => $b?->alamat_kantor,
        'Telpon kantor' => $b?->telp_kantor,
        'Alamat e-mail' => $b->email ?? $mahasiswa->user->email,
    ];

    $dataPendidikan = [
        ['Nama Sekolah', $p?->nama_sekolah],
        ['Alamat Sekolah', $p?->alamat_sekolah],
        ['Tahun Lulus', $p?->tahun_lulus_sekolah],
        ['Nama Perguruan Tinggi', $p?->nama_pt],
        ['Jurusan/Program Studi', $p?->jurusan_prodi],
        ['Program', $p?->program],
        ['Tahun Lulus', $p?->tahun_lulus_pt],
    ];
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Form 1 — {{ $mahasiswa->user->name }}</title>
    <style>
        @page { margin: 40px 50px; }
        body { font-family: 'Times New Roman', Times, serif; font-size: 12px; color: #000; line-height: 1.4; }

        .form-no { text-align: right; font-weight: bold; font-size: 12px; margin: 0 0 10px 0; }
        .judul { text-align: center; font-weight: bold; font-size: 13px; margin: 0 0 18px 0; }
        .bagian { font-weight: bold; margin: 0 0 6px 0; }
        .petunjuk { margin: 0 0 14px 0; text-align: justify; }
        .sub { font-weight: bold; margin: 14px 0 6px 0; }
        .sub-ket { font-weight: normal; }

        table.data { width: 100%; border-collapse: collapse; }
        table.data td { border: 0.75px solid #000; padding: 5px 7px; vertical-align: top; }
        table.data td.label { width: 34%; }
        table.data td.sep { width: 3%; text-align: center; border-left: none; border-right: none; }
        table.data td.value { border-left: none; }

        table.plain { width: 100%; border-collapse: collapse; }
        table.plain td { padding: 4px 0; vertical-align: top; }
        table.plain td.label { width: 34%; }
        table.plain td.sep { width: 3%; }
        table.plain td.value { border-bottom: 0.5px dotted #555; }

        .meta { margin-top: 26px; font-size: 9px; color: #555; }
    </style>
</head>
<body>
    <p class="form-no">FORM 1</p>

    <p class="judul">FORMULIR APLIKASI REKOGNISI PEMBELAJARAN LAMPAU (RPL) TIPE A</p>

    <p class="bagian">Bagian 1 : Rincian Data Peserta / Calon peserta</p>
    <p class="petunjuk">Pada bagian ini, cantumkan data pribadi, data pendidikan formal serta data pekerjaan saudara pada saat ini.</p>

    <p class="sub">a. Data Pribadi</p>
    <table class="data">
        @foreach ($dataPribadi as $label => $nilai)
            <tr>
                <td class="label">{{ $label }}</td>
                <td class="sep">:</td>
                <td class="value">{{ $isi($nilai) }}</td>
            </tr>
        @endforeach
    </table>

    <p class="sub">
        b. Data Pendidikan Terakhir
        <span class="sub-ket">(Hanya diisi dengan pendidikan formal Tingkat Sekolah Menengah Atas atau sederajat dan Perguruan Tinggi (apabila ada dilampiri foto kopi ijazah dan transkrip nilai).</span>
    </p>
    <table class="plain">
        @foreach ($dataPendidikan as [$label, $nilai])
            <tr>
                <td class="label">{{ $label }}</td>
                <td class="sep">:</td>
                <td class="value">{{ $isi($nilai) }}</td>
            </tr>
        @endforeach
    </table>

    <p class="meta">
        {{ $mahasiswa->prodi->nama }} · NIM {{ $mahasiswa->nim }} · Dicetak dari SIRAMA {{ now()->locale('id')->translatedFormat('d F Y') }}
    </p>
</body>
</html>
