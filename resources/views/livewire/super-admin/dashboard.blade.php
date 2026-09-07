<div>
    <x-page-header title="Dashboard Super Admin" subtitle="Ringkasan data seluruh program studi dan sistem RPL." />

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Program Studi" :value="$stats['prodi']" accent="indigo" icon="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21H3m16 0h-2M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5">
            <p class="mt-1 text-xs text-slate-500">{{ $stats['prodi_aktif'] }} aktif</p>
        </x-stat-card>

        <x-stat-card label="Mahasiswa RPL" :value="$stats['mahasiswa']" accent="emerald" icon="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4" />

        <x-stat-card label="Asesor Terdaftar" :value="$stats['asesor']" accent="amber" icon="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0118 20.417 12.083 12.083 0 0112 22a12.083 12.083 0 01-6.16-11.839L12 14z" />

        <x-stat-card label="Admin Prodi" :value="$stats['admin_prodi']" accent="rose" icon="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
    </div>

    <div class="mt-8 rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-900">Program Studi & Jumlah Mahasiswa RPL</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-medium uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Kode</th>
                        <th class="px-6 py-3">Nama Prodi</th>
                        <th class="px-6 py-3">Jenjang</th>
                        <th class="px-6 py-3">Mahasiswa</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($prodiList as $prodi)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-3 font-medium text-slate-900">{{ $prodi->kode }}</td>
                            <td class="px-6 py-3 text-slate-700">{{ $prodi->nama }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ $prodi->jenjang }}</td>
                            <td class="px-6 py-3 text-slate-500">{{ $prodi->mahasiswa_count }}</td>
                            <td class="px-6 py-3">
                                <x-badge :color="$prodi->is_aktif ? 'emerald' : 'slate'">
                                    {{ $prodi->is_aktif ? 'Aktif' : 'Non-aktif' }}
                                </x-badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">Belum ada program studi. <a href="{{ route('super-admin.prodi') }}" wire:navigate class="text-indigo-600 hover:underline">Tambahkan sekarang</a>.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
