<?php

namespace Database\Seeders;

use App\Models\Asesor;
use App\Models\Cpmk;
use App\Models\Mahasiswa;
use App\Models\Matakuliah;
use App\Models\PenugasanAsesor;
use App\Models\Prodi;
use App\Models\Skema;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@rpl.test'],
            [
                'name' => 'Super Admin',
                'username' => 'superadmin',
                'password' => Hash::make('password'),
            ]
        );
        $superAdmin->assignRole('super_admin');

        $prodi = Prodi::firstOrCreate(
            ['kode' => 'AKM'],
            ['nama' => 'Akuntansi Manajerial', 'jenjang' => 'D4', 'is_aktif' => true]
        );

        $adminProdi = User::firstOrCreate(
            ['email' => 'adminprodi@rpl.test'],
            [
                'name' => 'Admin Prodi Akuntansi Manajerial',
                'username' => 'adminprodi',
                'password' => Hash::make('password'),
            ]
        );
        $adminProdi->assignRole('admin_prodi');
        $adminProdi->prodiDikelola()->syncWithoutDetaching([$prodi->id]);

        $matakuliahData = [
            ['kode' => 'MKB56404', 'nama' => 'Pendidikan Pancasila', 'sks' => 2, 'skor_minimum' => 66],
            ['kode' => 'MKB56405', 'nama' => 'Bahasa Indonesia', 'sks' => 2, 'skor_minimum' => 66],
            ['kode' => 'MKB56201', 'nama' => 'Akuntansi Keuangan 1', 'sks' => 3, 'skor_minimum' => 66],
            ['kode' => 'MKB56202', 'nama' => 'Akuntansi Keuangan 2', 'sks' => 3, 'skor_minimum' => 66],
            ['kode' => 'MKB56310', 'nama' => 'Bahasa Inggris Akuntansi 2', 'sks' => 2, 'skor_minimum' => 78],
        ];

        foreach ($matakuliahData as $mk) {
            $matakuliah = Matakuliah::firstOrCreate(
                ['prodi_id' => $prodi->id, 'kode' => $mk['kode']],
                [
                    'nama' => $mk['nama'],
                    'sks' => $mk['sks'],
                    'skor_minimum' => $mk['skor_minimum'],
                    'dapat_direkognisi' => true,
                    'is_aktif' => true,
                ]
            );

            if ($matakuliah->cpmk()->count() === 0) {
                foreach ([1, 2] as $urutan) {
                    Cpmk::create([
                        'matakuliah_id' => $matakuliah->id,
                        'kode' => "CPMK{$urutan}",
                        'deskripsi' => "Mampu menjelaskan konsep dasar {$mk['nama']} (CPMK {$urutan}).",
                        'urutan' => $urutan,
                    ]);
                }
            }
        }

        $skema = Skema::firstOrCreate(
            ['prodi_id' => $prodi->id, 'nama' => 'Akuntansi Manajerial — Skema RPL', 'tahun_akademik' => '2026/2027'],
            ['jenis' => 'RPL', 'is_aktif' => true]
        );
        $skema->matakuliah()->syncWithoutDetaching(Matakuliah::where('prodi_id', $prodi->id)->pluck('id'));

        $asesorUsers = [
            ['name' => 'Dr. Ni Made Asesor Satu', 'username' => 'asesor1', 'email' => 'asesor1@rpl.test', 'nidn' => '0011223301'],
            ['name' => 'Drs. I Wayan Asesor Dua', 'username' => 'asesor2', 'email' => 'asesor2@rpl.test', 'nidn' => '0011223302'],
            ['name' => 'Dra. Ida Ayu Asesor Tiga', 'username' => 'asesor3', 'email' => 'asesor3@rpl.test', 'nidn' => '0011223303'],
        ];

        $asesorModels = [];
        foreach ($asesorUsers as $a) {
            $user = User::firstOrCreate(
                ['email' => $a['email']],
                ['name' => $a['name'], 'username' => $a['username'], 'password' => Hash::make('password')]
            );
            $user->assignRole('asesor');
            $asesorModels[] = Asesor::firstOrCreate(
                ['user_id' => $user->id],
                ['nidn' => $a['nidn'], 'bidang_keahlian' => 'Akuntansi', 'is_aktif' => true]
            );
        }

        $mahasiswaUser = User::firstOrCreate(
            ['email' => 'mahasiswa1@rpl.test'],
            ['name' => 'Ni Kadek Mahasiswa Contoh', 'username' => 'mahasiswa1', 'password' => Hash::make('password')]
        );
        $mahasiswaUser->assignRole('mahasiswa');

        $mahasiswa = Mahasiswa::firstOrCreate(
            ['user_id' => $mahasiswaUser->id],
            [
                'prodi_id' => $prodi->id,
                'skema_id' => $skema->id,
                'nim' => '2026010001',
                'tahun_masuk' => 2026,
                'status_berkas' => 'draft',
            ]
        );

        foreach ($asesorModels as $i => $asesorModel) {
            PenugasanAsesor::firstOrCreate([
                'mahasiswa_id' => $mahasiswa->id,
                'urutan' => $i + 1,
            ], [
                'asesor_id' => $asesorModel->id,
                'assigned_at' => now(),
            ]);
        }
    }
}
