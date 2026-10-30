<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekapitulasi {{ $mahasiswa->user->name }}</title>
    <style>
        @page { margin: 20px 28px; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 10px; color: #1e293b; }

        .kop-table { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .kop-table td { vertical-align: middle; padding: 0; }
        .kop-logo { width: 70px; text-align: center; }
        .kop-logo img { width: 60px; height: 60px; }
        .kop-text { text-align: center; }
        .kop-text .kementerian { font-size: 10px; margin: 0; }
        .kop-text .institusi { font-size: 16px; font-weight: bold; margin: 1px 0; text-transform: uppercase; }
        .kop-text .kontak { font-size: 8.5px; margin: 0; color: #475569; }
        .kop-rule { border: none; border-top: 2.5px solid #1e293b; border-bottom: 0.75px solid #1e293b; height: 0; margin: 4px 0 10px 0; }

        .form-no { text-align: right; font-size: 10px; font-weight: bold; margin: 0; }
        .judul { text-align: center; font-size: 13px; font-weight: bold; text-transform: uppercase; margin: 0 0 2px 0; }
        .subjudul { text-align: center; font-size: 10px; margin: 0 0 14px 0; color: #475569; }

        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 9.5px; }
        .info-table td { padding: 1.5px 0; vertical-align: top; }
        .info-label { width: 140px; color: #475569; }
        .info-sep { width: 10px; }
        .info-value { font-weight: bold; }

        table.rekap { width: 100%; border-collapse: collapse; font-size: 8.5px; }
        table.rekap th, table.rekap td { border: 0.75px solid #94a3b8; padding: 3px 4px; }
        table.rekap th { background-color: #f1f5f9; font-weight: bold; text-align: center; }
        table.rekap td.center { text-align: center; }
        table.rekap td.mk-nama { text-align: left; }
        .star { color: #4338ca; }
        .muted { color: #94a3b8; }
        .catatan-penilai { font-size: 7px; color: #d97706; display: block; }

        .footer-note { margin-top: 10px; font-size: 8px; color: #64748b; }

        .ttd-table { width: 100%; border-collapse: collapse; margin-top: 34px; font-size: 9.5px; }
        .ttd-table td { vertical-align: top; padding: 0; }
        .ttd-kanan { width: 260px; text-align: center; }
        .ttd-space { height: 55px; }
        .ttd-nama { font-weight: bold; text-decoration: underline; }
    </style>
</head>
<body>
    <table class="kop-table">
        <tr>
            <td class="kop-logo">
                @if (config('rpl.institusi.logo_path') && file_exists(config('rpl.institusi.logo_path')))
                    <img src="{{ config('rpl.institusi.logo_path') }}" alt="Logo">
                @endif
            </td>
            <td class="kop-text">
                <p class="kementerian">{{ config('rpl.institusi.kementerian') }}</p>
                <p class="institusi">{{ config('rpl.institusi.nama') }}</p>
                <p class="kontak">{{ config('rpl.institusi.alamat') }}</p>
                <p class="kontak">{{ config('rpl.institusi.kontak') }}</p>
            </td>
            <td class="kop-logo"></td>
        </tr>
    </table>
    <hr class="kop-rule">

    <p class="form-no">FORM {{ $nomorForm }}</p>
    <p class="judul">Formulir Rekapitulasi Hasil Asesmen untuk Program Studi</p>
    <p class="subjudul">{{ $mahasiswa->prodi->nama }} — Jalur Rekognisi Pembelajaran Lampau{{ $hanyaDiajukan ? ' (mata kuliah yang diajukan)' : '' }}</p>

    <table class="info-table">
        <tr>
            <td class="info-label">Nama</td><td class="info-sep">:</td><td class="info-value">{{ $mahasiswa->biodata->nama_lengkap ?? $mahasiswa->user->name }}</td>
            <td class="info-label">No HP</td><td class="info-sep">:</td><td class="info-value">{{ $mahasiswa->biodata->telp_rumah_hp ?? '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">NIM</td><td class="info-sep">:</td><td class="info-value">{{ $mahasiswa->nim }}</td>
            <td class="info-label">Email</td><td class="info-sep">:</td><td class="info-value">{{ $mahasiswa->biodata->email ?? $mahasiswa->user->email }}</td>
        </tr>
        <tr>
            <td class="info-label">Alamat</td><td class="info-sep">:</td><td class="info-value">{{ $mahasiswa->biodata->alamat_rumah ?? '—' }}</td>
            <td class="info-label">Jenjang Pendidikan Sebelumnya</td><td class="info-sep">:</td><td class="info-value">{{ $jenjangLabel }}</td>
        </tr>
    </table>

    <table class="rekap">
        <thead>
            <tr>
                <th style="width: 20px;">No</th>
                <th style="width: 65px;">Kode MK</th>
                <th>Matakuliah</th>
                <th style="width: 45px;">Skor Mandiri</th>
                @foreach ($asesorUrutan as $penugasan)
                    <th style="width: 40px;">Asesor {{ $penugasan->urutan }}</th>
                @endforeach
                <th style="width: 55px;">Rata-rata</th>
                <th style="width: 40px;">Skor Min.</th>
                <th style="width: 70px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($baris as $i => $row)
                <tr>
                    <td class="center">{{ $i + 1 }}</td>
                    <td class="center">{{ $row->matakuliah->kode }}</td>
                    <td class="mk-nama">{{ $row->matakuliah->nama }}@if ($row->matakuliah->dapat_direkognisi) <span class="star">*</span>@endif</td>
                    <td class="center">{{ $row->pengajuan?->nilai_mandiri_angka ?? '—' }}</td>
                    @foreach ($row->nilai_per_asesor as $nilai)
                        <td class="center">{{ $nilai ?? '—' }}</td>
                    @endforeach
                    <td class="center">
                        @if ($row->rata_rata !== null)
                            <strong>{{ $row->rata_rata }}</strong>
                            @if ($row->jumlah_penilai < $row->total_asesor)
                                <span class="catatan-penilai">({{ $row->jumlah_penilai }}/{{ $row->total_asesor }} asesor)</span>
                            @endif
                        @else
                            <span class="muted">—</span>
                        @endif
                    </td>
                    <td class="center">{{ $row->matakuliah->skor_minimum }}</td>
                    <td class="center">{{ $row->status ?? '—' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ 6 + $asesorUrutan->count() }}" class="center muted">Mahasiswa belum memiliki skema aktif.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p class="footer-note">Matakuliah bertanda * dapat direkognisi. Status ditetapkan melalui rapat pleno program studi. Dicetak pada {{ now()->translatedFormat('d F Y, H:i') }} WITA.</p>

    <table class="ttd-table">
        <tr>
            <td></td>
            <td class="ttd-kanan">
                <p>{{ config('rpl.institusi.alamat') }}, {{ now()->translatedFormat('d F Y') }}</p>
                <p>Ketua Program Studi {{ $mahasiswa->prodi->nama }}</p>
                <div class="ttd-space"></div>
                <p class="ttd-nama">(.........................................)</p>
                <p>NIP.</p>
            </td>
        </tr>
    </table>
</body>
</html>
