<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">

        <title>{{ isset($title) ? $title.' · '.config('app.name') : config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="bg-slate-50 text-slate-900 antialiased" x-data="{ sidebarOpen: false }">
        <div class="flex min-h-screen">
            {{-- Sidebar --}}
            <aside
                class="fixed inset-y-0 left-0 z-30 w-64 shrink-0 transform bg-slate-900 text-slate-200 transition-transform duration-150 ease-in-out lg:static lg:translate-x-0"
                :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            >
                <div class="flex h-16 items-center gap-2 border-b border-slate-800 px-5">
                    <div class="flex h-8 w-8 items-center justify-center rounded bg-blue-600 text-sm font-semibold text-white">
                        SPJ
                    </div>
                    <span class="text-sm font-semibold tracking-wide text-white">Sistem SPJ Otomatis</span>
                </div>

                <nav class="space-y-6 px-3 py-5 text-sm">
                    <div>
                        <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Umum</p>
                        <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                            Dashboard
                        </x-nav-link>
                    </div>

                    @can('projects.viewAny')
                        <div>
                            <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Project</p>

                            <x-nav-link :href="route('projects.index')" :active="request()->routeIs('projects.*')">
                                Project
                            </x-nav-link>

                            @can('clients.viewAny')
                                <x-nav-link :href="route('clients.index')" :active="request()->routeIs('clients.*')">
                                    Klien
                                </x-nav-link>
                            @endcan

                            @can('personnel.viewAny')
                                <x-nav-link :href="route('personnel.index')" :active="request()->routeIs('personnel.*')">
                                    Personel
                                </x-nav-link>
                            @endcan
                        </div>
                    @endcan

                    @can('users.viewAny')
                        <div>
                            <p class="px-2 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">Administrasi</p>

                            @can('organizations.viewAny')
                                <x-nav-link :href="route('organizations.index')" :active="request()->routeIs('organizations.*')">
                                    Organisasi
                                </x-nav-link>
                            @endcan

                            @can('create', \App\Models\ProjectType::class)
                                <x-nav-link :href="route('project-types.index')" :active="request()->routeIs('project-types.*')">
                                    Jenis Project
                                </x-nav-link>
                            @endcan

                            @can('create', \App\Models\PersonnelCategory::class)
                                <x-nav-link :href="route('personnel-categories.index')" :active="request()->routeIs('personnel-categories.*')">
                                    Kategori Personel
                                </x-nav-link>
                            @endcan

                            @can('create', \App\Models\CostCategory::class)
                                <x-nav-link :href="route('cost-categories.index')" :active="request()->routeIs('cost-categories.*')">
                                    Kategori Biaya
                                </x-nav-link>
                            @endcan

                            @can('create', \App\Models\TaxType::class)
                                <x-nav-link :href="route('tax-types.index')" :active="request()->routeIs('tax-types.*')">
                                    Jenis Pajak
                                </x-nav-link>
                            @endcan

                            <x-nav-link :href="route('users.index')" :active="request()->routeIs('users.*')">
                                Pengguna
                            </x-nav-link>
                        </div>
                    @endcan
                </nav>
            </aside>

            {{-- Overlay for mobile sidebar --}}
            <div
                x-show="sidebarOpen"
                x-cloak
                @click="sidebarOpen = false"
                class="fixed inset-0 z-20 bg-slate-900/40 lg:hidden"
            ></div>

            <div class="flex min-h-screen flex-1 flex-col lg:pl-64">
                {{-- Topbar --}}
                <header class="sticky top-0 z-10 flex h-16 items-center justify-between border-b border-slate-200 bg-white px-4 sm:px-6">
                    <div class="flex items-center gap-3">
                        <button
                            @click="sidebarOpen = !sidebarOpen"
                            class="rounded p-2 text-slate-500 hover:bg-slate-100 lg:hidden"
                            aria-label="Buka menu"
                        >
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
                            </svg>
                        </button>

                        @isset($title)
                            <h1 class="text-base font-semibold text-slate-900">{{ $title }}</h1>
                        @endisset
                    </div>

                    <div class="flex items-center gap-3">
                        @auth
                            <div class="hidden text-right sm:block">
                                <p class="text-sm font-medium text-slate-900">{{ auth()->user()->name }}</p>
                                <p class="text-xs text-slate-500">
                                    {{ auth()->user()->getRoleNames()->first() ?? '—' }}
                                </p>
                            </div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button
                                    type="submit"
                                    class="rounded-md border border-slate-200 px-3 py-1.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
                                >
                                    Keluar
                                </button>
                            </form>
                        @endauth
                    </div>
                </header>

                @if (isset($breadcrumbs) && count($breadcrumbs))
                    <div class="border-b border-slate-200 bg-white px-4 py-2 sm:px-6">
                        <nav class="flex text-sm text-slate-500" aria-label="Breadcrumb">
                            @foreach ($breadcrumbs as $label => $url)
                                @if (! $loop->last)
                                    <a href="{{ $url }}" class="hover:text-slate-700">{{ $label }}</a>
                                    <span class="mx-2 text-slate-300">/</span>
                                @else
                                    <span class="font-medium text-slate-700">{{ $label }}</span>
                                @endif
                            @endforeach
                        </nav>
                    </div>
                @endif

                <main class="flex-1 px-4 py-6 sm:px-6">
                    @if (session('status'))
                        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ $slot }}
                </main>
            </div>
        </div>

        @livewireScripts
    </body>
</html>
