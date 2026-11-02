<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\Session;

/**
 * Lets admins choose how many rows a paginated table shows. One session key
 * is shared by every table, so the choice carries over between menus.
 * Use alongside WithPagination and pass barisPerHalaman() to paginate().
 */
trait WithBarisPerHalaman
{
    #[Session(key: 'baris_per_halaman')]
    public int $perPage = 15;

    public function updatedPerPage(): void
    {
        $this->resetPage();
    }

    protected function barisPerHalaman(): int
    {
        $pilihan = config('rpl.pilihan_baris_per_halaman');

        // The value round-trips through the browser, so only accept a listed option.
        return in_array($this->perPage, $pilihan, true) ? $this->perPage : $pilihan[0];
    }
}
