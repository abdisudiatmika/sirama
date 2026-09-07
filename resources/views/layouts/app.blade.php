@php
    $user = auth()->user();
    $navItems = [];

    if ($user?->hasRole('super_admin')) {
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'super-admin.dashboard', 'icon' => 'home'],
            ['label' => 'Program Studi', 'route' => 'super-admin.prodi', 'icon' => 'building'],
            ['label' => 'Admin Prodi', 'route' => 'super-admin.admin-prodi', 'icon' => 'users'],
            ['label' => 'Direktori Asesor', 'route' => 'super-admin.asesor', 'icon' => 'academic-cap'],
        ];
    } elseif ($user?->hasRole('admin_prodi')) {
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'admin-prodi.dashboard', 'icon' => 'home'],
            ['label' => 'Matakuliah & CPMK', 'route' => 'admin-prodi.matakuliah', 'icon' => 'book-open'],
            ['label' => 'Skema', 'route' => 'admin-prodi.skema', 'icon' => 'squares'],
            ['label' => 'Asesor', 'route' => 'admin-prodi.asesor', 'icon' => 'academic-cap'],
            ['label' => 'Mahasiswa', 'route' => 'admin-prodi.mahasiswa', 'icon' => 'users'],
        ];
    } elseif ($user?->hasRole('asesor')) {
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'asesor.dashboard', 'icon' => 'home'],
        ];
    } elseif ($user?->hasRole('mahasiswa')) {
        $navItems = [
            ['label' => 'Dashboard', 'route' => 'mahasiswa.dashboard', 'icon' => 'home'],
        ];
    }

    $icons = [
        'home' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6',
        'building' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2M5 21H3m16 0h-2M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5',
        'users' => 'M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4-4 4 4 0 004 4zm6 0a4 4 0 10-4-4',
        'academic-cap' => 'M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422A12.083 12.083 0 0118 20.417 12.083 12.083 0 0112 22a12.083 12.083 0 01-6.16-11.839L12 14z',
        'book-open' => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
        'squares' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ isset($title) ? $title.' — '.config('app.name') : config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
        <div class="flex min-h-screen">
            <!-- Mobile overlay -->
            <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-30 bg-slate-950/50 lg:hidden"></div>

            <!-- Sidebar -->
            <aside
                class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-slate-950 transition-transform duration-200 lg:static lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <div class="flex h-16 items-center gap-3 border-b border-white/10 px-6">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422A12.083 12.083 0 0118 20.417 12.083 12.083 0 0112 22a12.083 12.083 0 01-6.16-11.839L12 14z" />
                        </svg>
                    </div>
                    <span class="text-base font-semibold text-white">SIM RPL</span>
                </div>

                <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-4 scrollbar-thin">
                    @foreach ($navItems as $item)
                        @php $isActive = request()->routeIs($item['route'].'*'); @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            wire:navigate
                            class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                                {{ $isActive ? 'bg-indigo-600 text-white' : 'text-slate-400 hover:bg-white/5 hover:text-white' }}"
                        >
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icons[$item['icon']] }}" />
                            </svg>
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </nav>

                <div class="border-t border-white/10 p-3">
                    <a href="{{ route('ubah-password') }}" wire:navigate class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-white">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 11-12 0 6 6 0 0112 0zM3 21l4.879-4.879" />
                        </svg>
                        Ubah Password
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-400 hover:bg-white/5 hover:text-white">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Keluar
                        </button>
                    </form>
                </div>
            </aside>

            <!-- Main -->
            <div class="flex flex-1 flex-col lg:pl-0">
                <header class="sticky top-0 z-20 flex h-16 items-center justify-between border-b border-slate-200 bg-white/80 px-4 backdrop-blur sm:px-6">
                    <div class="flex items-center gap-4">
                        <button @click="sidebarOpen = true" class="text-slate-500 hover:text-slate-700 lg:hidden">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>
                        <div>
                            <h1 class="text-lg font-semibold text-slate-900">{{ $title ?? '' }}</h1>
                            @isset($subtitle)
                                <p class="text-sm text-slate-500">{{ $subtitle }}</p>
                            @endisset
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="hidden text-right sm:block">
                            <p class="text-sm font-medium text-slate-900">{{ $user?->name }}</p>
                            <p class="text-xs text-slate-500">{{ ucwords(str_replace('_', ' ', $user?->getRoleNames()->first() ?? '')) }}</p>
                        </div>
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700">
                            {{ strtoupper(substr($user?->name ?? '?', 0, 1)) }}
                        </div>
                    </div>
                </header>

                <main class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                    @if (session('success'))
                        <div class="mb-6 flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="mb-6 flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                            </svg>
                            {{ session('error') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
