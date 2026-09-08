<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="min-h-screen bg-white font-sans antialiased">
        <div class="flex min-h-screen">
            <div class="relative hidden w-1/2 flex-col justify-between overflow-hidden bg-gradient-to-br from-blue-700 via-blue-800 to-blue-900 p-12 lg:flex">
                <div class="pointer-events-none absolute inset-0 overflow-hidden">
                    <div class="absolute -top-24 -right-24 h-80 w-80 rounded-full bg-white/5"></div>
                    <div class="absolute top-1/3 -left-32 h-96 w-96 rounded-full bg-white/5"></div>
                    <div class="absolute -bottom-32 right-1/4 h-72 w-72 rounded-full bg-blue-500/20"></div>
                </div>

                <div class="relative z-10 flex items-center gap-3">
                    <div class="flex h-14 w-14 items-center justify-center overflow-hidden rounded-full bg-white/10 ring-1 ring-white/20">
                        @if (file_exists(public_path('images/logo-pnb.png')))
                            <img src="{{ asset('images/logo-pnb.png') }}" alt="Logo Politeknik Negeri Bali" class="h-10 w-10 object-contain">
                        @else
                            <svg class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3l8 4.5v9L12 21l-8-4.5v-9L12 3z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v8m-4-4h8" />
                            </svg>
                        @endif
                    </div>
                    <span class="text-sm font-semibold uppercase tracking-widest text-white/90">Politeknik Negeri Bali</span>
                </div>

                <div class="relative z-10 max-w-md">
                    <h1 class="text-4xl font-bold leading-tight text-white">
                        Sistem Rekrutmen Mahasiswa RPL
                    </h1>
                    <p class="mt-4 text-blue-100">
                        Pengelolaan formulir, asesmen transfer SKS, dan hasil rekognisi dalam satu sistem.
                    </p>
                </div>

                <div class="relative z-10 flex items-center gap-2 text-sm text-blue-100">
                    <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.75h-.152c-3.196 0-6.1-1.248-8.25-3.286z" />
                    </svg>
                    Akses aman sesuai peran pengguna
                </div>
            </div>

            <div class="flex w-full items-center justify-center bg-white p-6 lg:w-1/2">
                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
