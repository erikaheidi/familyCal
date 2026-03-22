<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <link href="https://fonts.bunny.net/css?family=fredoka:400,500,600" rel="stylesheet" />
    </head>
    <body class="min-h-screen bg-[radial-gradient(circle_at_top,#fff7ed,transparent_60%),radial-gradient(circle_at_bottom,#ecfeff,transparent_65%),linear-gradient(to_bottom,#ffffff,#fef3c7)] text-slate-900 dark:bg-[radial-gradient(circle_at_top,#1f2937,transparent_55%),radial-gradient(circle_at_bottom,#0b1020,transparent_65%),linear-gradient(to_bottom,#0b0b0f,#111827)] dark:text-slate-100 font-['Fredoka']">
        <div class="relative overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute -top-36 -left-28 h-80 w-80 rounded-full bg-gradient-to-br from-amber-300 via-rose-300 to-sky-300 opacity-80 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute left-1/2 top-10 h-64 w-64 -translate-x-1/2 rounded-full bg-gradient-to-br from-fuchsia-300 via-rose-200 to-orange-200 opacity-60 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-44 right-0 h-96 w-96 rounded-full bg-gradient-to-br from-lime-200 via-emerald-200 to-cyan-300 opacity-80 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute inset-x-0 top-24 h-64 bg-[radial-gradient(circle_at_top,rgba(254,215,170,0.45),transparent_60%)]"></div>
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_30%_20%,rgba(125,211,252,0.25),transparent_55%),radial-gradient(circle_at_70%_25%,rgba(251,191,36,0.2),transparent_60%),radial-gradient(circle_at_50%_80%,rgba(167,243,208,0.25),transparent_55%)]"></div>

            <header class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between px-6 pt-6">
                <a href="{{ route('home') }}" class="flex items-center gap-3" wire:navigate>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-amber-100 dark:bg-[#151521] dark:ring-white/10">
                        <x-app-logo-icon class="h-7 w-7 text-amber-500 dark:text-amber-300" />
                    </span>
                    <div class="leading-tight">
                        <div class="text-lg font-semibold">{{ config('app.name', 'FamilyCal') }}</div>
                        <div class="text-xs text-slate-500 dark:text-slate-300">Shared moments, gently organized</div>
                    </div>
                </a>

                @if (Route::has('login'))
                    <nav class="flex items-center gap-2 text-sm">
                        @auth
                            <a href="{{ route('dashboard') }}" class="rounded-full border border-slate-200 bg-white px-4 py-2 font-medium text-slate-900 shadow-sm transition hover:-translate-y-0.5 hover:border-slate-300 dark:border-white/10 dark:bg-white/10 dark:text-white" wire:navigate>
                                Dashboard
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="rounded-full border border-transparent px-4 py-2 font-medium text-slate-700 transition hover:text-slate-900 dark:text-slate-200 dark:hover:text-white" wire:navigate>
                                Log in
                            </a>

                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="rounded-full border border-amber-200 bg-amber-400/90 px-4 py-2 font-semibold text-amber-950 shadow-sm transition hover:-translate-y-0.5 hover:bg-amber-400 dark:border-amber-300/40 dark:bg-amber-300/90" wire:navigate>
                                    Register
                                </a>
                            @endif
                        @endauth
                    </nav>
                @endif
            </header>

            <main class="relative z-10 mx-auto w-full max-w-6xl px-6 pb-16 pt-10">
                <livewire:public.calendar />
            </main>
        </div>
        @fluxScripts
    </body>
</html>
