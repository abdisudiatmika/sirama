# PRD — Sistem Informasi Penerimaan Mahasiswa RPL

**Versi:** 0.5 (draft)
**Tanggal:** 7 September 2026
**Penyusun:** I Putu Gede Abdi Sudiatmika
**Status:** Draft untuk review
**Lampiran acuan:** FORM 1 — Rincian Data Peserta (.docx) · FORM 3 — Daftar Pelatihan dan Pengalaman Kerja (.docx) · Formulir Rekapitulasi Hasil Asesmen (screenshot)

---

## 1. Ringkasan Produk

Aplikasi web untuk mengelola proses penerimaan mahasiswa jalur **RPL (Rekognisi Pembelajaran Lampau)**, mulai dari pendataan mahasiswa, pengajuan bukti pembelajaran lampau, pemetaan bukti ke matakuliah/CPMK, sampai penilaian oleh asesor dan rekapitulasi nilai oleh admin prodi.

**Masalah yang diselesaikan:** proses RPL saat ini berbasis dokumen manual (Word/Excel) sehingga sulit ditelusuri, rawan salah hitung, dan tidak ada jejak penilaian per asesor.

**Prinsip desain utama:**
1. **Server tidak menyimpan berkas bukti.** Semua bukti mahasiswa berupa *link* (Google Drive / YouTube) yang di-*view* oleh asesor. Ini menekan biaya storage dan risiko kebocoran data.
2. **Data master milik prodi, akun asesor milik institusi.** Matakuliah dan skema bersifat per-prodi; akun asesor dipakai bersama lintas prodi.
3. **Semua nilai punya jejak.** Setiap nilai selalu terhubung ke asesor, matakuliah, jenis bukti, dan catatan.

---

## 2. Istilah

| Istilah | Arti |
|---|---|
| RPL | Rekognisi Pembelajaran Lampau |
| CPMK | Capaian Pembelajaran Mata Kuliah |
| Skema | Paket matakuliah yang ditawarkan untuk direkognisi (mis. Skema RPL, Skema Reguler) |
| Bukti formal | Ijazah, transkrip nilai |
| Bukti informal | Video, surat pengalaman kerja, sertifikat non-akademik |
| VATM | Kriteria validasi bukti: **V**alid, **A**sli, **T**erkini, **M**emadai |
| Asesmen | Proses penilaian oleh asesor terhadap satu matakuliah milik satu mahasiswa |

---

## 3. Peran Pengguna & Hak Akses

Terdapat 4 level login.

| Fitur | Super Admin | Admin Prodi | Mahasiswa | Asesor |
|---|:--:|:--:|:--:|:--:|
| Kelola Prodi | ✅ | — | — | — |
| Kelola akun Admin Prodi | ✅ | — | — | — |
| Kelola akun Asesor | ✅ | ✅ (buat/pakai yang ada) | — | — |
| Kelola Matakuliah & CPMK | — | ✅ (prodi sendiri) | — | — |
| Aktif/non-aktifkan matakuliah | — | ✅ | — | — |
| Kelola Skema | — | ✅ | — | — |
| Kelola akun Mahasiswa | — | ✅ | — | — |
| Kunci/buka akses mahasiswa | — | ✅ | — | — |
| Penugasan asesor ke mahasiswa | — | ✅ | — | — |
| Isi Form 1–6 | — | — | ✅ | — |
| Melakukan asesmen | — | — | — | ✅ |
| Lihat nilai per asesor & rekap | ✅ (read-only) | ✅ | ✅ (hasil akhir, opsional) | — |

**Aturan lintas prodi**
- Akun **asesor bersifat global**: bila asesor sudah dibuat oleh Super Admin atau prodi lain, prodi tidak perlu membuat ulang — cukup mencari dan menugaskan.
- **Matakuliah bersifat lokal prodi**: hanya bisa diakses oleh mahasiswa pada prodi tersebut.

---

## 4. Alur Utama (End-to-End)

```
Super Admin  →  buat Prodi, Admin Prodi, Asesor
      ↓
Admin Prodi  →  input Matakuliah + CPMK  →  buat Skema (RPL / Reguler) + pilih MK
             →  buat akun Mahasiswa  →  tugaskan hingga 3 Asesor
      ↓
Mahasiswa    →  Form 1 Biodata
             →  Form 2 Daftar Bukti (link)
             →  Form 3 Pemetaan MK + Bukti + Nilai Mandiri
             →  Form 4 Upload Daftar Riwayat Hidup (ber-TTD)
             →  Form 5 Self-assessment CPMK (Mampu / Belum Mampu)
             →  Form 6 Data MK Asal (khusus bukti formal)
             →  SUBMIT (berkas terkunci)
      ↓
Asesor       →  validasi VATM per CPMK  →  nilai 0–100 per jenis bukti
             →  catatan reviewer + catatan kesenjangan  →  simpan
      ↓
Sistem       →  hitung nilai akhir per MK
      ↓
Admin Prodi  →  monitoring status per asesor  →  Rekapitulasi  →  Export
```

---

## 5. Kebutuhan Fungsional

### 5.1 Modul Autentikasi (F-AUTH)

| ID | Kebutuhan |
|---|---|
| F-AUTH-01 | Satu halaman login untuk semua peran; sistem mendeteksi peran dan mengarahkan ke dashboard masing-masing. |
| F-AUTH-02 | Password disimpan dalam bentuk *hash* (bcrypt/argon2), tidak pernah plaintext. |
| F-AUTH-03 | Bila akun mahasiswa berstatus **terkunci**, login ditolak dengan pesan: *"Akun Anda terkunci. Silakan hubungi admin prodi."* |
| F-AUTH-04 | Fitur ubah password mandiri untuk semua peran. |
| F-AUTH-05 | Reset password oleh admin di atasnya (Super Admin → Admin Prodi/Asesor; Admin Prodi → Mahasiswa). |
| F-AUTH-06 | Session timeout otomatis (default 60 menit idle) dan tombol logout. |
| F-AUTH-07 | Pembatasan percobaan login gagal (mis. 5 kali → jeda 15 menit). |

---

### 5.2 Modul Super Admin (F-SA)

| ID | Kebutuhan |
|---|---|
| F-SA-01 | CRUD **Prodi**: kode prodi, nama prodi, jenjang (D3/D4/S1), status aktif. |
| F-SA-02 | CRUD **Akun Admin Prodi**: nama, email, username, password awal, prodi yang dikelola. Satu admin dapat dipetakan ke satu atau lebih prodi. |
| F-SA-03 | CRUD **Akun Asesor**: nama, NIP/NIDN, email, no. HP, bidang keahlian, status aktif. Akun ini otomatis tersedia untuk seluruh prodi. |
| F-SA-04 | Dashboard ringkas: jumlah prodi, jumlah mahasiswa RPL, jumlah asesor, progres asesmen global. |
| F-SA-05 | Akses read-only ke seluruh data prodi untuk keperluan audit. |
| F-SA-06 | Log aktivitas (audit trail) untuk aksi yang mengubah data master dan nilai. |

---

### 5.3 Modul Admin Prodi (F-AP)

#### A. Manajemen Matakuliah & CPMK

| ID | Kebutuhan |
|---|---|
| F-AP-01 | CRUD matakuliah: kode MK, nama MK (sesuai CP Prodi), SKS, semester, **skor minimum rekognisi**, penanda *dapat direkognisi*, status aktif/non-aktif. |
| F-AP-01a | **Skor minimum** ditetapkan per matakuliah (default prodi mis. 66, dapat berbeda per MK — contoh Bahasa Inggris Akuntansi 2 = 78). Nilai ini muncul pada kolom "Skor Minimum" di rekapitulasi. |
| F-AP-01b | Penanda **dapat direkognisi** — pada dokumen manual ditandai tanda bintang `*` di belakang nama MK (dikonfirmasi). MK tanpa penanda tetap tampil di rekap agar daftar utuh, namun tidak dapat diajukan mahasiswa pada Form 3. Tanda `*` ikut dicetak pada export rekap. |
| F-AP-02 | **Import massal** matakuliah via unggah file Excel/CSV, dengan template unduhan dan pratinjau validasi sebelum simpan (laporan baris gagal + alasan). |
| F-AP-03 | Toggle **aktif/non-aktif** matakuliah. Matakuliah non-aktif tidak muncul di form mahasiswa, tetapi data historis yang sudah dinilai tetap utuh. |
| F-AP-04 | CRUD **CPMK** per matakuliah: kode CPMK, deskripsi CPMK, urutan. Satu MK memiliki 1..n CPMK. |
| F-AP-05 | Import massal CPMK via Excel/CSV. |
| F-AP-06 | Validasi: matakuliah tidak dapat dihapus bila sudah dipakai pada skema atau sudah dinilai — hanya dapat di-non-aktifkan. |

#### B. Manajemen Skema

| ID | Kebutuhan |
|---|---|
| F-AP-07 | CRUD **Skema** milik prodi, contoh: "Akuntansi Manajerial — Skema RPL", "Akuntansi Manajerial — Skema Reguler". Field: nama skema, jenis (RPL/Reguler/lainnya), tahun akademik, status aktif. |
| F-AP-08 | Pilih matakuliah anggota skema (multi-select dari daftar MK aktif prodi). Satu MK boleh berada di lebih dari satu skema. |
| F-AP-09 | Ringkasan skema: jumlah MK dan total SKS yang dapat direkognisi. |

#### C. Manajemen Asesor

| ID | Kebutuhan |
|---|---|
| F-AP-10 | Pencarian asesor pada direktori global sebelum membuat baru; sistem memperingatkan bila email/NIDN sudah terdaftar untuk mencegah duplikasi. |
| F-AP-11 | Admin prodi dapat membuat akun asesor baru bila belum ada; akun tersebut otomatis menjadi bagian direktori global. |

#### D. Manajemen Mahasiswa

| ID | Kebutuhan |
|---|---|
| F-AP-12 | CRUD **akun mahasiswa**: nama, NIM/no. pendaftaran, email, username, password awal, prodi, **skema yang diikuti**, tahun masuk. |
| F-AP-13 | Import massal akun mahasiswa via Excel/CSV. |
| F-AP-14 | **Kunci / buka kunci** akses mahasiswa (per individu dan massal). |
| F-AP-15 | **Penugasan asesor**: memilih maksimal 3 asesor untuk satu mahasiswa. Dapat diubah selama asesor tersebut belum memasukkan nilai (jika sudah, sistem meminta konfirmasi karena nilai akan dilepas dari mahasiswa). |
| F-AP-16 | Monitoring kelengkapan berkas mahasiswa: status per Form 1–6 (Belum / Draft / Lengkap). |

#### E. Monitoring & Rekapitulasi

| ID | Kebutuhan |
|---|---|
| F-AP-17 | Daftar mahasiswa disertai **status penilaian tiap asesor**, mis. `Asesor 1: 30/60 · Asesor 2: 0/60 · Asesor 3: 60/60`. |
| F-AP-18 | Detail per mahasiswa: nilai dari Asesor 1, 2, dan 3 untuk setiap matakuliah, berdampingan dalam satu tabel. |
| F-AP-19 | **Formulir Rekapitulasi Hasil Asesmen untuk Program Studi** — lihat §6.5 untuk struktur kolom persis. |
| F-AP-19a | Kolom **Status** diisi manual oleh admin prodi sebagai hasil **rapat pleno**; sistem hanya memberi usulan otomatis (Rata-rata Asesmen ≥ Skor Minimum → usul "Direkognisi"). Keputusan akhir tetap pada rapat. |
| F-AP-19b | Rekap menampilkan **seluruh matakuliah pada skema**, termasuk yang tidak diajukan mahasiswa (baris nilai dikosongkan), agar sesuai format dokumen resmi. |
| F-AP-20 | Export rekap ke Excel dan PDF dengan logo dan kop institusi, layout identik dengan formulir manual, serta ruang tanda tangan. |
| F-AP-21 | Filter dan pencarian: per skema, per angkatan, per asesor, per status. |

---

### 5.4 Modul Mahasiswa (F-MHS)

Alur bertahap (*wizard*) 6 form. Tombol **Simpan Draft** tersedia di setiap tahap; mahasiswa dapat kembali ke form sebelumnya selama berkas belum di-submit final.

#### Form 1 — Biodata Diri

| ID | Kebutuhan |
|---|---|
Mengacu pada **FORM 1 — Formulir Aplikasi Rekognisi Pembelajaran Lampau (RPL) Tipe A, Bagian 1: Rincian Data Peserta / Calon Peserta**.

**F-MHS-01 — Sub-bagian A: Data Pribadi**

| Field | Tipe | Wajib | Catatan |
|---|---|:--:|---|
| Nama lengkap | Teks | ✅ | |
| Tempat lahir | Teks | ✅ | Dipisah dari tanggal agar dapat diolah |
| Tanggal lahir | Date picker | ✅ | Tidak boleh di masa depan |
| Jenis kelamin | Pilihan | ✅ | Laki-laki / Perempuan |
| Status | Pilihan | ✅ | Belum Menikah / Menikah / Lainnya |
| Kebangsaan | Teks | ✅ | Default: Indonesia |
| Alamat rumah | Teks panjang | ✅ | |
| Kode pos | Teks | — | Numerik, 5 digit |
| Telepon rumah / HP | Teks | ✅ | Minimal salah satu terisi |
| Alamat kantor | Teks panjang | — | |
| Telepon kantor | Teks | — | |
| Alamat e-mail | Email | ✅ | Validasi format |

**F-MHS-02 — Sub-bagian B: Data Pendidikan Terakhir**
Diisi untuk pendidikan formal jenjang SMA/sederajat dan Perguruan Tinggi. Bila tersedia, ijazah dan transkrip nilai dilampirkan sebagai bukti pada Form 2.

| Field | Tipe | Wajib |
|---|---|:--:|
| Nama Sekolah (SMA/sederajat) | Teks | ✅ |
| Alamat Sekolah | Teks panjang | — |
| Tahun Lulus (Sekolah) | Angka (tahun) | ✅ |
| Nama Perguruan Tinggi | Teks | — |
| Jurusan / Program Studi | Teks | — |
| Program (D3/D4/S1/S2) | Pilihan | — |
| Tahun Lulus (Perguruan Tinggi) | Angka (tahun) | — |

| ID | Kebutuhan |
|---|---|
| F-MHS-03 | Sistem menampilkan teks pengantar sesuai formulir asli: *"Pada bagian ini, cantumkan data pribadi, data pendidikan formal serta data pekerjaan saudara pada saat ini."* |
| F-MHS-03a | Bagian Data Pendidikan Terakhir disertai keterangan bahwa fotokopi ijazah dan transkrip nilai dilampirkan (diarahkan ke Form 2). |
| F-MHS-03b | Validasi: field wajib, format email, format nomor telepon (hanya angka, +, dan spasi), tahun lulus ≤ tahun berjalan, tanggal lahir tidak di masa depan. |
| F-MHS-03c | Tidak dapat lanjut ke Form 2 sebelum seluruh field wajib terisi. |
| F-MHS-03d | Data Nama, Alamat rumah, No. HP, E-mail, dan Jenjang Pendidikan sebelumnya otomatis mengisi kepala Formulir Rekapitulasi Hasil Asesmen (lihat F-AP-19). |

#### Form 2 — Pendaftaran Bukti

| ID | Kebutuhan |
|---|---|
| F-MHS-04 | Mahasiswa menambahkan bukti satu per satu. Langkah: pilih **Kategori** → pilih **Jenis Bukti** → isi detail + link. |
| F-MHS-05 | Kategori **Formal** → jenis bukti: *Ijazah*, *Transkrip Nilai*. Kategori **Informal** → jenis bukti: *Video*, *Surat Pengalaman Kerja*. (Daftar jenis bukti dapat dikelola sebagai data master agar mudah ditambah.) |
| F-MHS-06 | Tersedia tombol **Kembali** untuk mengganti pilihan kategori/jenis sebelum bukti disimpan. |
| F-MHS-07 | Input bukti berupa **link** Google Drive / YouTube / dokumen ter-*share*. **Tidak ada penyimpanan berkas di server.** |
| F-MHS-08 | Field per bukti: judul bukti, jenis, penerbit/instansi, tahun, URL, keterangan singkat. |
| F-MHS-09 | Validasi format URL; peringatan bila link bukan pola Google Drive/YouTube yang dikenali; tombol **Uji Link** yang membuka tautan di tab baru agar mahasiswa memastikan aksesnya publik/*view*. |
| F-MHS-10 | Daftar bukti yang sudah ditambahkan dapat diedit dan dihapus selama berkas belum di-submit. |
| F-MHS-11 | Minimal 1 bukti harus terdaftar sebelum lanjut ke Form 3. |

#### Form 3 — Pemetaan Matakuliah, Bukti, dan Nilai Mandiri

| ID | Kebutuhan |
|---|---|
| F-MHS-12 | Menampilkan daftar matakuliah **aktif** dari skema yang diikuti mahasiswa, beserta CPMK masing-masing (dapat dibuka/tutup). |
| F-MHS-13 | Mahasiswa memilih matakuliah yang diajukan untuk direkognisi (centang). |
| F-MHS-14 | Untuk tiap MK terpilih, mahasiswa melampirkan bukti dengan memilih dari daftar bukti Form 2. **Satu MK boleh lebih dari satu bukti** (mis. Ijazah + Surat Pengalaman Kerja). |
| F-MHS-15 | Untuk tiap MK, mahasiswa mengisi **nilai mandiri**: nilai angka (0–100) dan nilai huruf. Contoh: Pancasila → 80 / A (mengacu transkrip); Bahasa Indonesia dengan bukti pengalaman kerja → 80 / A (penilaian diri). |
| F-MHS-16 | Sistem menandai secara otomatis **jenis penilaian** yang akan muncul untuk asesor: Formal saja, Informal saja, atau keduanya — berdasarkan kategori bukti yang dilampirkan. |
| F-MHS-17 | Validasi: setiap MK terpilih wajib memiliki ≥1 bukti dan nilai mandiri terisi. |
| F-MHS-18 | Nilai huruf dipilih dari daftar terkontrol (A, AB, B, BC, C, D, E — mengikuti standar institusi). |

#### Form 4 — Daftar Riwayat Hidup / Daftar Pelatihan dan Pengalaman Kerja

Mengacu pada template **FORM 3 — Daftar Pelatihan dan Pengalaman Kerja**, yang memuat dua bagian: daftar riwayat hidup (terlampir) dan **Pernyataan Calon Peserta** bertanda tangan.

| ID | Kebutuhan |
|---|---|
| F-MHS-19 | Tombol **Unduh Template** "Daftar Pelatihan dan Pengalaman Kerja" (berkas disediakan sistem, dapat diganti oleh admin prodi). |
| F-MHS-20 | Mahasiswa mengisi **link** dokumen yang sudah dilengkapi dan ditandatangani (Google Drive atau layanan berbagi lain), sama seperti mekanisme bukti pada Form 2. **Tidak ada berkas yang disimpan di server.** |
| F-MHS-20a | Mahasiswa wajib mengatur izin akses link minimal *"siapa saja yang memiliki link dapat melihat"* dan **mengizinkan unduhan**, agar asesor dan admin prodi dapat membuka sekaligus mengunduh dokumen untuk keperluan berkas pleno. Sistem menampilkan panduan singkat pengaturan izin ini di samping field link. |
| F-MHS-20b | Tersedia tombol **Uji Link** yang membuka tautan di tab baru untuk memastikan dokumen dapat diakses. |
| F-MHS-20c | **Pernyataan Calon Peserta ditampilkan di layar** sebagai teks persetujuan yang harus dicentang, berisi empat butir sesuai template: (1) seluruh informasi benar dan bersedia menerima sanksi bila tidak benar; (2) memberi izin kepada Tim RPL memeriksa kebenaran informasi kepada institusi pendidikan dan tempat kerja terkait; (3) bersedia melengkapi berkas untuk proses asesmen dan rekognisi; (4) bersedia mengikuti proses asesmen sesuai jadwal dan melunasi biaya pendaftaran. |
| F-MHS-20d | Nama prodi dan perguruan tinggi pada teks pernyataan terisi otomatis dari data akun mahasiswa (contoh: "Program Studi Akuntansi Manajerial pada Perguruan Tinggi Politeknik Negeri Bali"). |
| F-MHS-20e | Field **Tempat** dan **Tanggal** pernyataan diisi mahasiswa; nama penanda tangan terisi otomatis dari biodata. Waktu persetujuan dan alamat IP dicatat sebagai jejak audit. |
| F-MHS-21 | Link dokumen dapat dibuka (*view*) dan diunduh oleh asesor serta admin prodi melalui tombol pada halaman berkas mahasiswa. |
| F-MHS-21a | Centang pernyataan bersifat wajib; tanpa itu mahasiswa tidak dapat lanjut ke Form 5. |

#### Form 5 — Self-Assessment CPMK

| ID | Kebutuhan |
|---|---|
| F-MHS-22 | Menampilkan seluruh matakuliah yang dipilih pada Form 3 beserta seluruh CPMK-nya. |
| F-MHS-23 | Setiap CPMK memiliki dua pilihan eksklusif: **Mampu** / **Belum Mampu**. |
| F-MHS-24 | Tersedia tombol **Centang Semua "Mampu"** per matakuliah dan untuk seluruh daftar. |
| F-MHS-25 | Validasi: seluruh CPMK harus terisi salah satu pilihan sebelum lanjut. Indikator progres, mis. `42/50 CPMK terisi`. |

#### Form 6 — Data Matakuliah Asal (khusus bukti formal)

| ID | Kebutuhan |
|---|---|
| F-MHS-26 | Menampilkan **hanya matakuliah yang dilampiri bukti formal** (Ijazah / Transkrip Nilai) pada Form 3. MK yang hanya berbukti informal **tidak muncul**. Contoh: Pancasila muncul (bukti transkrip); Bahasa Indonesia tidak muncul (bukti pengalaman kerja). |
| F-MHS-27 | Untuk tiap MK yang muncul, mahasiswa mengisi: **Kode MK Asal** (mis. MK001), **Nama MK Asal** (mis. Pendidikan Pancasila), **CPMK Asal** (mis. "Mampu menjelaskan…"). |
| F-MHS-28 | CPMK asal dapat diisi lebih dari satu baris per matakuliah. |
| F-MHS-29 | Validasi field wajib sebelum submit final. |

#### Submit & Status Berkas

| ID | Kebutuhan |
|---|---|
| F-MHS-30 | Halaman **Ringkasan** sebelum submit: menampilkan seluruh isian Form 1–6 untuk diperiksa ulang. |
| F-MHS-31 | Setelah **Submit Final**, berkas terkunci (read-only) dan masuk antrean asesmen. Perubahan hanya dimungkinkan bila admin prodi membuka kembali (status → Draft) dengan alasan tercatat. |
| F-MHS-32 | Mahasiswa dapat memantau status: *Draft → Diajukan → Sedang Dinilai → Selesai Dinilai*. |
| F-MHS-33 | Tampilan hasil akhir untuk mahasiswa (opsional, dikontrol pengaturan prodi): daftar MK, nilai akhir, status rekognisi. |

---

### 5.5 Modul Asesor (F-ASR)

| ID | Kebutuhan |
|---|---|
| F-ASR-01 | Menu **Penilaian / Asesmen** menampilkan daftar mahasiswa yang ditugaskan kepada asesor tersebut. |
| F-ASR-02 | Tiap baris menampilkan: nama mahasiswa, prodi, skema, dan **progres penilaian** dalam format `dinilai/total`, mis. `30/60`. |
| F-ASR-03 | Membuka mahasiswa → daftar matakuliah yang harus dinilai, disertai status per MK (Belum Dinilai / Selesai) dan jenis bukti yang dilampirkan. |
| F-ASR-04 | Membuka matakuliah → daftar **CPMK**, masing-masing dengan 4 *checkbox* kriteria: **Valid**, **Asli**, **Terkini**, **Memadai**. |
| F-ASR-05 | Tersedia tombol **Centang Semua** (per CPMK dan untuk seluruh CPMK matakuliah tersebut). |
| F-ASR-06 | Panel bukti: daftar bukti yang dilampirkan mahasiswa untuk MK tersebut, dapat dibuka (*view*) melalui link, plus tampilan nilai mandiri mahasiswa dan self-assessment Form 5 sebagai referensi. |
| F-ASR-07 | **Form penilaian bersyarat:** bila MK hanya berbukti formal → **1 form** penilaian. Bila hanya informal → **1 form**. Bila formal **dan** informal → **2 form** terpisah (Penilaian Formal dan Penilaian Informal). |
| F-ASR-08 | Setiap form penilaian berisi: **Nilai (0–100)**, **Catatan Reviewer** (teks panjang), **Catatan Kesenjangan** (teks panjang). |
| F-ASR-09 | Tombol **Simpan** per matakuliah. Setelah tersimpan, progres asesor bertambah dan nilai akhir MK dihitung ulang. |
| F-ASR-10 | Asesor dapat mengubah nilai yang sudah disimpan selama periode asesmen belum ditutup oleh admin prodi; perubahan tercatat di audit log. |
| F-ASR-11 | Asesor hanya dapat melihat mahasiswa yang ditugaskan kepadanya, dan tidak dapat melihat nilai dari asesor lain. |
| F-ASR-12 | Navigasi cepat "Simpan & Matakuliah Berikutnya". |

---

## 6. Aturan Bisnis Penilaian

### 6.1 Rumus Nilai Akhir per Matakuliah (per asesor)

| Kondisi bukti | Rumus nilai akhir |
|---|---|
| Formal saja | `Nilai Akhir = Nilai Formal` |
| Informal saja | `Nilai Akhir = Nilai Informal` |
| Formal + Informal | `Nilai Gabungan = Nilai Formal + (10% × Nilai Informal)`, dibatasi maksimum **85**. **Bila Nilai Formal > 85, yang dipakai adalah Nilai Formal.** |

**Rumus lengkap untuk kondisi Formal + Informal**

```
gabungan   = nilai_formal + (0,1 × nilai_informal)
nilai_akhir = MAX( nilai_formal , MIN(gabungan, 85) )
```

Aturan `MAX` memastikan mahasiswa **tidak pernah dirugikan** karena melampirkan bukti tambahan: nilai akhir selalu minimal sama dengan nilai formalnya.

**Contoh perhitungan**

| Nilai Formal | Nilai Informal | Perhitungan | Nilai Akhir |
|---|---|---|---|
| 75 | 80 | 75 + 8 = 83 (< 85) | 83 |
| 80 | 90 | 80 + 9 = 89 → dibatasi 85 | **85** |
| 90 | 80 | Gabungan 98 → batas 85, tetapi formal 90 > 85 | **90** |
| 86 | 60 | Gabungan 92 → batas 85, tetapi formal 86 > 85 | **86** |
| — | 78 | Informal saja | 78 |
| 82 | — | Formal saja | 82 |

**Catatan implementasi:** batas 85 hanya membatasi *tambahan nilai dari bukti informal*, bukan capaian formal itu sendiri. Nilai formal murni di atas 85 tetap dipakai apa adanya, baik ada bukti informal maupun tidak.

### 6.2 Konversi Nilai Huruf

Tabel konversi disimpan sebagai data master agar dapat disesuaikan tanpa mengubah kode. Nilai default usulan:

| Rentang | Huruf | Bobot |
|---|---|---|
| 80 – 100 | A | 4,00 |
| 71 – 79 | AB | 3,50 |
| 65 – 70 | B | 3,00 |
| 60 – 64 | BC | 2,50 |
| 55 – 59 | C | 2,00 |
| 40 – 54 | D | 1,00 |
| 0 – 39 | E | 0,00 |

### 6.3 Definisi Progres Asesor
Satu matakuliah dihitung **selesai** bila seluruh form penilaian yang berlaku untuk MK tersebut telah tersimpan (2 form bila bukti formal + informal). Progres = `jumlah MK selesai / jumlah MK ditugaskan`.

### 6.4 Agregasi Nilai 3 Asesor

Sesuai Formulir Rekapitulasi, nilai final per matakuliah adalah **Rata-rata Asesmen**:

```
Rata-rata Asesmen = ROUND( Σ nilai akhir tiap asesor / jumlah asesor yang sudah menilai )
```

Contoh dari formulir acuan:
- Akuntansi Keuangan 2 → 78, 78, 79 → rata-rata **78**
- Akuntansi Keuangan 1 → 76, 77, 79 → rata-rata **77**

Ketentuan:
- **Pembagi = jumlah asesor yang sudah menilai**, bukan jumlah asesor yang ditugaskan. Bila baru 2 dari 3 asesor menilai, rata-rata dihitung dari 2 nilai tersebut. Asesor yang belum menilai tidak dihitung sebagai nol.
- **Pembulatan standar** (ke bawah dan ke atas, *round half up*): pecahan < 0,5 dibulatkan ke bawah, ≥ 0,5 dibulatkan ke atas. Contoh: 78,33 → 78 · 77,49 → 77 · 77,50 → 78 · 78,67 → 79.
- Nilai per asesor pada rekap adalah **nilai akhir per matakuliah** hasil rumus §6.1 (sudah menggabungkan bukti formal/informal), bukan nilai mentah per jenis bukti.
- Karena pembagi mengikuti jumlah penilai, rata-rata dapat **berubah** saat asesor berikutnya menyimpan nilai. Sistem menampilkan penanda visual dan keterangan jumlah penilai, mis. `78 (2 dari 3 asesor)`, sampai seluruh asesor selesai.
- Bila belum ada satu pun asesor yang menilai, kolom rata-rata dikosongkan (bukan 0).
- Nilai tiap asesor tetap ditampilkan berdampingan agar keputusan pleno dapat ditelusuri.
- Nilai yang dipakai pada rapat pleno adalah rata-rata pada saat periode asesmen ditutup oleh admin prodi.

### 6.5 Struktur Formulir Rekapitulasi Hasil Asesmen

**Kepala dokumen** (otomatis dari Form 1):

| Label | Sumber |
|---|---|
| Nama | Biodata — nama lengkap |
| Alamat | Biodata — alamat rumah |
| No HP | Biodata — telepon rumah/HP |
| Email | Biodata — alamat e-mail |
| Jenjang Pendidikan sebelumnya | Data Pendidikan Terakhir (mis. "S1 Undiksha") |

**Tabel utama:**

| Kolom | Sumber data | Keterangan |
|---|---|---|
| No | Sistem | Nomor urut |
| Kode Mata kuliah | `matakuliah.kode` | mis. MKB56404 |
| Matakuliah (sesuai dengan CP Prodi) | `matakuliah.nama` | Tanda `*` = dapat direkognisi |
| Skor Mandiri | Form 3 — nilai mandiri mahasiswa | |
| Hasil asesmen — Asesor RPL 1 | `penilaian` asesor urutan 1 | Nilai akhir MK per asesor |
| Hasil asesmen — Asesor RPL 2 | `penilaian` asesor urutan 2 | |
| Hasil asesmen — Asesor RPL 3 | `penilaian` asesor urutan 3 | |
| Rata-rata Asesmen | Dihitung sistem | §6.4 |
| Skor Minimum | `matakuliah.skor_minimum` | Default prodi, dapat berbeda per MK |
| Status | Input manual admin prodi | *Diisi hasil rapat pleno* |

Urutan asesor pada kolom 1/2/3 mengikuti field `urutan` pada penugasan asesor agar konsisten antar-halaman dan antar-export.

---

## 7. Arsitektur & Teknologi

### 7.1 Tumpukan Teknologi

| Lapisan | Pilihan | Alasan |
|---|---|---|
| Framework backend | **Laravel 12.x** (PHP 8.3+) | Ekosistem matang, Eloquent ORM, migration, policy/gate untuk RBAC, banyak SDM lokal |
| Antarmuka | **Blade + Livewire 3 + Alpine.js** | Wizard Form 1–6 dan halaman asesmen banyak interaksi (centang massal, form dinamis) namun tetap satu bahasa (PHP) — tanpa SPA terpisah |
| CSS | **Tailwind CSS** | Cepat membangun tabel rekap dan form padat, konsisten |
| Basis data | **MySQL 8 / MariaDB 10.6+** | Standar di lingkungan kampus; PostgreSQL 14+ juga didukung tanpa perubahan kode |
| Autentikasi | **Laravel Fortify** atau **Breeze** | Login, throttle, reset password, ubah password sudah tersedia |
| Otorisasi peran | **spatie/laravel-permission** | Role: super_admin, admin_prodi, mahasiswa, asesor |
| Import Excel | **maatwebsite/excel** | Import massal matakuliah, CPMK, akun mahasiswa (F-AP-02, F-AP-05, F-AP-13) |
| Export PDF | **barryvdh/laravel-dompdf** (atau Spatie Browsershot bila layout rekap kompleks) | Cetak Formulir Rekapitulasi berkop |
| Audit log | **owen-it/laravel-auditing** | Merekam perubahan nilai dan data master (F-SA-06) |
| Antrian | **Queue driver: database** | Import massal dan generate PDF dijalankan asinkron |
| Pengujian | **Pest / PHPUnit** | Wajib untuk rumus nilai §6.1 dan rata-rata §6.4 |
| Server | Nginx + PHP-FPM, Supervisor untuk queue worker, cron untuk `schedule:run` | Deployment umum di server kampus |

**Alternatif** bila tim lebih nyaman dengan Vue/React: Laravel + **Inertia.js**. Struktur backend pada PRD ini tidak berubah — hanya lapisan tampilan.

### 7.2 Pola Implementasi Kunci

**Isolasi data antar-prodi**
Gunakan **Global Scope** Eloquent pada model `Matakuliah`, `Skema`, `Mahasiswa` yang otomatis memfilter `prodi_id` sesuai prodi admin yang login, dipadukan dengan **Policy** per model. Filter tidak boleh hanya di query controller — global scope mencegah kebocoran data bila ada endpoint baru yang lupa memfilter.

**Akun asesor global**
Model `Asesor` **tidak** dikenai scope prodi. Relasi ke mahasiswa dibentuk lewat tabel pivot `penugasan_asesor`, sehingga satu asesor dapat menilai mahasiswa lintas prodi tanpa duplikasi akun (F-AP-10, F-AP-11).

**Perhitungan nilai**
Seluruh rumus §6.1 dan §6.4 diletakkan pada satu **service class** (mis. `App\Services\PenilaianService`), bukan tersebar di controller, Blade, atau query. Alasannya: rumus batas 85 dan aturan `MAX(formal, …)` adalah titik paling rawan bug dan paling mungkin berubah kebijakan. Nilai akhir dihitung saat penilaian disimpan lalu di-*cache* pada kolom `penilaian.nilai_akhir` agar tabel rekap tidak menghitung ulang ratusan baris tiap kali dibuka.

**Wizard Form 1–6**
Setiap form adalah Livewire component dengan **auto-save draft**. Status kelengkapan disimpan pada `mahasiswa.status_berkas` dan flag per form, sehingga mahasiswa dapat melanjutkan dari titik terakhir. Validasi memakai **Form Request** di sisi server, bukan hanya di browser.

**Penanganan link bukti**
Bukti dan dokumen Form 4 disimpan sebagai URL. Validasi memakai *custom validation rule* yang memeriksa pola domain Google Drive / YouTube dan format URL. Sistem **tidak** mengambil (fetch) isi dokumen; pembukaan dilakukan di sisi klien lewat tab baru. Tidak ada `Storage::put()` untuk berkas mahasiswa — hanya template unduhan milik institusi yang tersimpan di server.

**Struktur direktori (garis besar)**

```
app/
├── Models/            Prodi, Matakuliah, Cpmk, Skema, Mahasiswa, Asesor, Penilaian, ...
├── Policies/          MatakuliahPolicy, MahasiswaPolicy, PenilaianPolicy
├── Services/          PenilaianService, RekapService, ImportService
├── Livewire/
│   ├── Mahasiswa/     Form1Biodata ... Form6MkAsal
│   ├── Asesor/        DaftarAsesmen, FormPenilaian
│   └── AdminProdi/    Matakuliah, Skema, Mahasiswa, Rekapitulasi
└── Http/Requests/     Validasi tiap form
database/
├── migrations/        Sesuai model data §8
└── seeders/           Role, prodi contoh, konversi nilai huruf
```

### 7.3 Konvensi

- Nama tabel *snake_case* jamak sesuai konvensi Eloquent; kolom mengikuti §8.
- Semua perubahan skema lewat **migration** (tidak ada perubahan manual di server).
- Konstanta kebijakan (batas 85, bobot 10%, default skor minimum) disimpan di file konfigurasi `config/rpl.php`, bukan *hard-coded*, agar mudah disesuaikan bila peraturan berubah.
- Bahasa antarmuka Indonesia via file lokalisasi `lang/id`.

---

## 8. Model Data (Ringkas)

| Tabel | Field utama |
|---|---|
| `prodi` | id, kode, nama, jenjang, status |
| `users` | id, username, email, password_hash, role, prodi_id, is_locked, last_login |
| `asesor` | id, user_id, nama, nidn, bidang_keahlian, status (global) |
| `matakuliah` | id, prodi_id, kode, nama, sks, semester, skor_minimum, dapat_direkognisi, is_aktif |
| `cpmk` | id, matakuliah_id, kode, deskripsi, urutan |
| `skema` | id, prodi_id, nama, jenis, tahun_akademik, status |
| `skema_matakuliah` | id, skema_id, matakuliah_id |
| `mahasiswa` | id, user_id, prodi_id, skema_id, nim, nama, status_berkas |
| `biodata` | id, mahasiswa_id, nama_lengkap, tempat_lahir, tanggal_lahir, jenis_kelamin, status, kebangsaan, alamat_rumah, kode_pos, telp_rumah_hp, alamat_kantor, telp_kantor, email |
| `pendidikan_terakhir` | id, mahasiswa_id, nama_sekolah, alamat_sekolah, tahun_lulus_sekolah, nama_pt, jurusan_prodi, program, tahun_lulus_pt |
| `pernyataan` | id, mahasiswa_id, disetujui, tempat, tanggal, waktu_setuju, ip_address |
| `hasil_pleno` | id, mahasiswa_id, matakuliah_id, status, catatan_pleno, ditetapkan_oleh, tanggal_pleno |
| `bukti` | id, mahasiswa_id, kategori (formal/informal), jenis_bukti, judul, penerbit, tahun, url, keterangan |
| `pengajuan_mk` | id, mahasiswa_id, matakuliah_id, nilai_mandiri_angka, nilai_mandiri_huruf |
| `pengajuan_bukti` | id, pengajuan_mk_id, bukti_id |
| `self_assessment` | id, pengajuan_mk_id, cpmk_id, status (mampu/belum_mampu) |
| `mk_asal` | id, pengajuan_mk_id, kode_asal, nama_mk_asal, cpmk_asal |
| `dokumen_cv` | id, mahasiswa_id, url, keterangan, updated_at *(link, bukan berkas server)* |
| `penugasan_asesor` | id, mahasiswa_id, asesor_id, urutan (1–3), assigned_at |
| `penilaian` | id, pengajuan_mk_id, asesor_id, jenis (formal/informal), nilai, catatan_reviewer, catatan_kesenjangan, created_at, updated_at |
| `validasi_cpmk` | id, penilaian_id, cpmk_id, valid, asli, terkini, memadai |
| `audit_log` | id, user_id, aksi, entitas, entitas_id, data_lama, data_baru, waktu |

**Batasan integritas penting**
- `penugasan_asesor`: maksimal 3 baris per mahasiswa (unique `mahasiswa_id + urutan`).
- `penilaian`: unique pada `pengajuan_mk_id + asesor_id + jenis`.
- `pengajuan_mk`: unique pada `mahasiswa_id + matakuliah_id`.
- Matakuliah tidak dapat dihapus bila terhubung ke `pengajuan_mk` atau `skema_matakuliah`.

---

## 9. Status & Alur Berkas

| Status | Pemicu | Hak edit mahasiswa |
|---|---|---|
| Draft | Akun dibuat / dibuka kembali oleh admin | ✅ |
| Diajukan | Mahasiswa submit final | ❌ |
| Sedang Dinilai | Minimal satu asesor menyimpan nilai | ❌ |
| Selesai Dinilai | Seluruh asesor menyelesaikan seluruh MK | ❌ |
| Dikembalikan | Admin prodi membuka kembali disertai alasan | ✅ |

---

## 10. Kebutuhan Non-Fungsional

**Keamanan**
- Otorisasi diperiksa di sisi server pada setiap request (bukan hanya menyembunyikan menu).
- Isolasi data antar-prodi: admin prodi A tidak dapat mengakses data prodi B.
- Proteksi CSRF, XSS, dan SQL injection; validasi input di sisi server.
- Audit log untuk seluruh perubahan nilai dan data master.
- HTTPS wajib.

**Privasi**
- Berkas bukti tidak disimpan di server; hanya URL. Peringatan kepada mahasiswa agar tidak membagikan tautan berisi data pribadi berlebihan.
- Data pribadi hanya diakses oleh peran yang berwenang.

**Kinerja**
- Halaman daftar merespons < 2 detik pada 500 mahasiswa aktif.
- Import Excel 1.000 baris selesai < 30 detik dengan laporan hasil.

**Kompatibilitas**
- Responsif: desktop dan tablet prioritas; mahasiswa dapat mengisi form dari ponsel.
- Browser modern (Chrome, Edge, Firefox, Safari versi terkini).

**Keandalan**
- Auto-save draft form mahasiswa (mencegah kehilangan data saat sesi habis).
- Backup basis data harian.

**Bahasa**
- Seluruh antarmuka berbahasa Indonesia.

---

## 11. Di Luar Lingkup (Non-Goals) Versi 1

- Integrasi otomatis ke SIAKAD / Feeder PDDikti (disiapkan lewat export, bukan API).
- Pembayaran/registrasi keuangan.
- Notifikasi email/WhatsApp otomatis (kandidat fase 2).
- Tanda tangan digital tersertifikasi pada berita acara.
- Unggah berkas apa pun ke server — seluruh dokumen (bukti Form 2 dan daftar riwayat hidup Form 4) berbasis link.

---

## 12. Pertanyaan Terbuka (perlu keputusan sebelum implementasi)

**Sudah diputuskan:** field Form 1 · isi template Form 4 · sumber matakuliah Form 6 = **Form 3** · agregasi nilai = **rata-rata**, pembagi = **jumlah asesor yang sudah menilai** · pembulatan = **standar (round half up)** · perlakuan batas 85 = **nilai formal dipakai bila > 85** · Form 4 = **berbasis link, dapat diunduh** · tanda `*` = **matakuliah dapat direkognisi** · ambang kelulusan lewat kolom Skor Minimum · status akhir ditetapkan rapat pleno.

**Masih perlu keputusan:**

1. **Skor minimum.** Ditetapkan per matakuliah oleh admin prodi (asumsi PRD ini), atau satu nilai default prodi dengan pengecualian manual? Pada contoh, mayoritas 66 dan Bahasa Inggris Akuntansi 2 bernilai 78.
2. **Jumlah asesor minimum.** Karena pembagi mengikuti jumlah penilai, perlu ditetapkan berapa asesor minimal agar suatu matakuliah boleh dibawa ke rapat pleno (mis. minimal 2 dari 3).
3. **Rapat pleno.** Apakah statusnya diinput per matakuliah, atau satu keputusan untuk seluruh berkas mahasiswa? Perlukah berita acara pleno yang dapat dicetak?
4. **Skema per mahasiswa.** Satu mahasiswa terikat pada satu skema saja, atau boleh mengajukan lintas skema? *(Terkait rencana pembagian kategori mahasiswa: reguler ±24 MK, dari hotel ±12 MK, LPD ±30 MK dari total ±36 MK.)*
5. **Batas jumlah SKS/MK** yang boleh direkognisi per mahasiswa — apakah dibatasi sistem?
6. **Nilai huruf mandiri** — dipilih bebas oleh mahasiswa atau dihitung otomatis dari nilai angka? Perlu dicatat bahwa formulir rekap resmi hanya memuat skor angka, bukan huruf.
7. **Biaya pendaftaran** disebut pada Pernyataan Calon Peserta. Apakah statusnya dicatat di sistem, atau di luar lingkup?

---

## 13. Prioritas Pengembangan

| Fase | Cakupan |
|---|---|
| **Fase 1 (MVP)** | Auth, Super Admin, Admin Prodi (MK, CPMK, skema, mahasiswa, asesor), Mahasiswa Form 1–6, Asesor asesmen, perhitungan nilai, rekap dasar |
| **Fase 2** | Import massal Excel, export PDF berkop, dashboard analitik, audit log lengkap |
| **Fase 3** | Notifikasi, integrasi SIAKAD, tanda tangan digital, arsip periode/tahun akademik |

---

*Dokumen ini adalah draft. Setelah pertanyaan pada §12 dijawab, versi 1.0 dapat difinalisasi beserta wireframe dan skema basis data lengkap.*
