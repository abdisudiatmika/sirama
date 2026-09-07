<?php

namespace App\Livewire\Concerns;

use App\Models\Prodi;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Shared prodi-scoping for Admin Prodi components. An admin can manage more
 * than one prodi (F-SA-02), so every list/CRUD query must be filtered by the
 * currently selected prodi rather than assuming a single-prodi admin.
 */
trait InteractsWithProdiContext
{
    public ?int $prodiId = null;

    public function mountInteractsWithProdiContext(): void
    {
        $ids = $this->prodiOptions()->pluck('id');
        $this->prodiId = $ids->first();
    }

    public function prodiOptions(): Collection
    {
        return Auth::user()->prodiDikelola()->orderBy('nama')->get();
    }

    public function updatedProdiId(): void
    {
        if (! $this->prodiOptions()->pluck('id')->contains($this->prodiId)) {
            abort(403);
        }

        if (method_exists($this, 'resetPage')) {
            $this->resetPage();
        }
    }

    public function currentProdi(): ?Prodi
    {
        return $this->prodiOptions()->firstWhere('id', $this->prodiId);
    }

    protected function assertProdiAuthorized(int $prodiId): void
    {
        abort_unless($this->prodiOptions()->pluck('id')->contains($prodiId), 403);
    }
}
