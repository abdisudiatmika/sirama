<?php

namespace App\Livewire\AdminProdi;

use App\Livewire\Concerns\InteractsWithProdiContext;
use App\Models\Asesor;
use App\Models\Mahasiswa;
use App\Models\PengembalianBerkas;
use App\Models\PenugasanAsesor;
use App\Models\Skema;
use App\Models\User;
use App\Services\Dokumen\DokumenMahasiswaService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Mahasiswa')]
class MahasiswaManager extends Component
{
    use InteractsWithProdiContext, WithPagination;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $username = '';

    public string $email = '';

    public string $nim = '';

    public int $tahun_masuk;

    public ?int $skema_id = null;

    public string $search = '';

    public ?string $generatedPassword = null;

    public ?string $generatedFor = null;

    // Penugasan asesor
    public bool $showAsesorModal = false;

    public ?int $asesorMahasiswaId = null;

    public string $asesorSearch = '';

    /** @var array<int,int|null> urutan(1..3) => asesor_id */
    public array $slotAsesor = [1 => null, 2 => null, 3 => null];

    // Kembalikan berkas
    public bool $showPengembalianModal = false;

    public ?int $pengembalianMahasiswaId = null;

    public string $alasanPengembalian = '';

    // Panel dokumen
    public ?int $panelMahasiswaId = null;

    public function mount(): void
    {
        $this->tahun_masuk = (int) now()->year;
    }

    protected function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username,'.$this->editingId],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.$this->editingId],
            'nim' => ['required', 'string', 'max:30', 'unique:mahasiswa,nim,'.$this->editingId],
            'tahun_masuk' => ['required', 'integer', 'min:2000', 'max:2100'],
            'skema_id' => ['nullable', 'exists:skema,id'],
        ];
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function tambah(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $mhs = Mahasiswa::with('user')->findOrFail($id);
        $this->assertProdiAuthorized($mhs->prodi_id);

        $this->editingId = $mhs->id;
        $this->name = $mhs->user->name;
        $this->username = $mhs->user->username;
        $this->email = $mhs->user->email;
        $this->nim = $mhs->nim;
        $this->tahun_masuk = $mhs->tahun_masuk;
        $this->skema_id = $mhs->skema_id;
        $this->showModal = true;
    }

    public function simpan(): void
    {
        $data = $this->validate();

        if ($this->editingId) {
            $mhs = Mahasiswa::findOrFail($this->editingId);
            $mhs->user->update([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
            ]);
            $mhs->update([
                'nim' => $data['nim'],
                'tahun_masuk' => $data['tahun_masuk'],
                'skema_id' => $data['skema_id'],
            ]);
            session()->flash('success', 'Data mahasiswa berhasil diperbarui.');
            $this->closeModal();

            return;
        }

        $password = Str::password(12);
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => Hash::make($password),
        ]);
        $user->assignRole('mahasiswa');

        Mahasiswa::create([
            'user_id' => $user->id,
            'prodi_id' => $this->prodiId,
            'skema_id' => $data['skema_id'],
            'nim' => $data['nim'],
            'tahun_masuk' => $data['tahun_masuk'],
            'status_berkas' => 'draft',
        ]);

        $this->generatedPassword = $password;
        $this->generatedFor = $user->username;
        $this->showModal = false;
        $this->resetForm();
    }

    public function toggleLock(int $id): void
    {
        $mhs = Mahasiswa::with('user')->findOrFail($id);
        $this->assertProdiAuthorized($mhs->prodi_id);
        $mhs->user->update(['is_locked' => ! $mhs->user->is_locked]);
        session()->flash('success', $mhs->user->is_locked ? 'Akses mahasiswa dikunci.' : 'Akses mahasiswa dibuka kembali.');
    }

    public function resetPassword(int $id): void
    {
        $mhs = Mahasiswa::with('user')->findOrFail($id);
        $this->assertProdiAuthorized($mhs->prodi_id);
        $password = Str::password(12);
        $mhs->user->update(['password' => Hash::make($password)]);

        $this->generatedPassword = $password;
        $this->generatedFor = $mhs->user->username;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function tutupInfoPassword(): void
    {
        $this->generatedPassword = null;
        $this->generatedFor = null;
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'name', 'username', 'email', 'nim', 'skema_id']);
        $this->tahun_masuk = (int) now()->year;
        $this->resetErrorBag();
    }

    // --- Penugasan Asesor ---

    public function kelolaAsesor(int $mahasiswaId): void
    {
        $mhs = Mahasiswa::findOrFail($mahasiswaId);
        $this->assertProdiAuthorized($mhs->prodi_id);

        $this->asesorMahasiswaId = $mahasiswaId;
        $this->slotAsesor = [1 => null, 2 => null, 3 => null];

        foreach ($mhs->penugasanAsesor as $penugasan) {
            $this->slotAsesor[$penugasan->urutan] = $penugasan->asesor_id;
        }

        $this->showAsesorModal = true;
    }

    public function simpanAsesor(): void
    {
        $mhs = Mahasiswa::findOrFail($this->asesorMahasiswaId);
        $this->assertProdiAuthorized($mhs->prodi_id);

        $terpilih = array_filter($this->slotAsesor);

        if (count($terpilih) !== count(array_unique($terpilih))) {
            session()->flash('error', 'Satu asesor tidak boleh dipilih lebih dari sekali untuk mahasiswa yang sama.');

            return;
        }

        foreach ($this->slotAsesor as $urutan => $asesorId) {
            if ($asesorId) {
                PenugasanAsesor::updateOrCreate(
                    ['mahasiswa_id' => $mhs->id, 'urutan' => $urutan],
                    ['asesor_id' => $asesorId, 'assigned_at' => now()]
                );
            } else {
                PenugasanAsesor::where('mahasiswa_id', $mhs->id)->where('urutan', $urutan)->delete();
            }
        }

        session()->flash('success', 'Penugasan asesor berhasil disimpan.');
        $this->closeAsesorModal();
    }

    public function closeAsesorModal(): void
    {
        $this->showAsesorModal = false;
        $this->asesorMahasiswaId = null;
        $this->asesorSearch = '';
        $this->slotAsesor = [1 => null, 2 => null, 3 => null];
    }

    // --- Kirim Paksa ke Asesor ---

    public function paksaKirimKeAsesor(int $mahasiswaId): void
    {
        $mhs = Mahasiswa::with('pengajuanMk', 'penugasanAsesor')->findOrFail($mahasiswaId);
        $this->assertProdiAuthorized($mhs->prodi_id);

        if (! in_array($mhs->status_berkas, ['draft', 'dikembalikan'])) {
            session()->flash('error', 'Berkas ini sudah diajukan.');

            return;
        }

        if ($mhs->pengajuanMk->isEmpty()) {
            session()->flash('error', 'Mahasiswa belum mengajukan matakuliah apa pun, tidak ada yang bisa dinilai.');

            return;
        }

        if ($mhs->penugasanAsesor->isEmpty()) {
            session()->flash('error', 'Tugaskan minimal 1 asesor terlebih dahulu sebelum mengirim berkas.');

            return;
        }

        $mhs->update(['status_berkas' => 'diajukan']);

        session()->flash('success', 'Berkas dikirim ke asesor tanpa menunggu mahasiswa submit. Kelengkapan data (mis. Form 6) tidak divalidasi ulang — pastikan sudah sesuai secara manual.');
    }

    // --- Kembalikan Berkas ---

    public function bukaPengembalian(int $mahasiswaId): void
    {
        $mhs = Mahasiswa::findOrFail($mahasiswaId);
        $this->assertProdiAuthorized($mhs->prodi_id);

        if (! in_array($mhs->status_berkas, ['diajukan', 'sedang_dinilai', 'selesai_dinilai'])) {
            session()->flash('error', 'Hanya berkas yang sudah diajukan yang bisa dikembalikan.');

            return;
        }

        $this->pengembalianMahasiswaId = $mahasiswaId;
        $this->alasanPengembalian = '';
        $this->showPengembalianModal = true;
    }

    public function simpanPengembalian(): void
    {
        $this->validate([
            'alasanPengembalian' => ['required', 'string', 'min:10'],
        ], [
            'alasanPengembalian.required' => 'Alasan pengembalian wajib diisi.',
            'alasanPengembalian.min' => 'Jelaskan alasan pengembalian lebih rinci (minimal 10 karakter).',
        ]);

        $mhs = Mahasiswa::findOrFail($this->pengembalianMahasiswaId);
        $this->assertProdiAuthorized($mhs->prodi_id);

        PengembalianBerkas::create([
            'mahasiswa_id' => $mhs->id,
            'alasan' => $this->alasanPengembalian,
            'dikembalikan_oleh' => Auth::id(),
        ]);

        $mhs->update(['status_berkas' => 'dikembalikan']);

        session()->flash('success', 'Berkas dikembalikan ke mahasiswa untuk diperbaiki.');
        $this->closePengembalianModal();
    }

    public function closePengembalianModal(): void
    {
        $this->showPengembalianModal = false;
        $this->pengembalianMahasiswaId = null;
        $this->alasanPengembalian = '';
        $this->resetErrorBag();
    }

    // --- Panel Dokumen ---

    public function bukaPanel(int $mahasiswaId): void
    {
        $mhs = Mahasiswa::findOrFail($mahasiswaId);
        $this->assertProdiAuthorized($mhs->prodi_id);

        $this->panelMahasiswaId = $mahasiswaId;
    }

    public function closePanel(): void
    {
        $this->panelMahasiswaId = null;
    }

    public function render()
    {
        $dokumen = app(DokumenMahasiswaService::class);

        $panelMahasiswa = $this->panelMahasiswaId
            ? Mahasiswa::with(['user', 'skema', 'biodata'])->find($this->panelMahasiswaId)
            : null;

        $mahasiswaList = Mahasiswa::where('prodi_id', $this->prodiId)
            ->with(['user', 'skema', 'penugasanAsesor.asesor.user'])
            ->when($this->search, function ($q) {
                $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"))
                    ->orWhere('nim', 'like', "%{$this->search}%");
            })
            ->orderByDesc('tahun_masuk')
            ->orderBy('nim')
            ->paginate(10);

        $skemaOptions = Skema::where('prodi_id', $this->prodiId)->where('is_aktif', true)->orderBy('nama')->get();

        $asesorOptions = Asesor::with('user')
            ->where('is_aktif', true)
            ->when($this->asesorSearch, fn ($q) => $q->whereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->asesorSearch}%")))
            ->join('users', 'users.id', '=', 'asesor.user_id')
            ->orderBy('users.name')
            ->select('asesor.*')
            ->limit(50)
            ->get();

        return view('livewire.admin-prodi.mahasiswa-manager', [
            'mahasiswaList' => $mahasiswaList,
            'skemaOptions' => $skemaOptions,
            'asesorOptions' => $asesorOptions,
            'prodiOptions' => $this->prodiOptions(),
            'panelMahasiswa' => $panelMahasiswa,
            'panelFormulir' => $panelMahasiswa ? $dokumen->daftarFormulir($panelMahasiswa) : [],
        ]);
    }
}
