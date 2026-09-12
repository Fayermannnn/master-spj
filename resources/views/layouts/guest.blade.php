<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="flex min-h-screen items-center justify-center bg-slate-50 px-4 text-slate-900 antialiased">
        <div class="w-full max-w-sm">
            <div class="mb-6 flex flex-col items-center gap-2">
                <div class="flex h-10 w-10 items-center justify-center rounded bg-blue-600 text-sm font-semibold text-white">
                    SPJ
                </div>
                <p class="text-sm font-semibold text-slate-700">Sistem SPJ Otomatis</p>
            </div>

            {{ $slot }}
        </div>

        @livewireScripts
    </body>
</html>
