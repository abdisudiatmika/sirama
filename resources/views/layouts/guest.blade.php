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
    <body class="min-h-screen bg-slate-950 font-sans antialiased">
        <div class="relative flex min-h-screen">
            <div class="pointer-events-none absolute inset-0 overflow-hidden">
                <div class="absolute -top-40 -left-40 h-96 w-96 rounded-full bg-indigo-600/20 blur-3xl"></div>
                <div class="absolute top-1/3 -right-32 h-96 w-96 rounded-full bg-teal-500/10 blur-3xl"></div>
                <div class="absolute bottom-0 left-1/4 h-72 w-72 rounded-full bg-indigo-500/10 blur-3xl"></div>
            </div>

            <div class="relative z-10 hidden w-1/2 flex-col justify-between p-12 lg:flex">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 ring-1 ring-white/20">
                        <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422A12.083 12.083 0 0118 20.417 12.083 12.083 0 0112 22a12.083 12.083 0 01-6.16-11.839L12 14z" />
                        </svg>
                    </div>
                    <span class="text-lg font-semibold text-white">SIM RPL</span>
                </div>

                <div class="max-w-md">
                    <h1 class="text-4xl font-bold leading-tight text-white">
                        Rekognisi Pembelajaran Lampau, tertata dari pendaftaran hingga rekapitulasi.
                    </h1>
                    <p class="mt-4 text-slate-400">
                        Satu sistem untuk mengelola pengajuan bukti, penilaian asesor, dan rekapitulasi hasil asesmen RPL secara transparan dan tertelusur.
                    </p>
                </div>

                <p class="text-sm text-slate-500">&copy; {{ date('Y') }} Sistem Informasi Penerimaan Mahasiswa RPL</p>
            </div>

            <div class="relative z-10 flex w-full items-center justify-center p-6 lg:w-1/2">
                <div class="w-full max-w-sm">
                    {{ $slot }}
                </div>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
