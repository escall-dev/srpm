<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
     <head>
         @include('partials.head')
         @vite(['resources/css/app.css', 'resources/js/app.js'])
         @include('partials.head-scripts')
     </head>
    <body class="bg-[#FDFDFC] bg-[url('/storage/app/public/assets/images/bg.jpg')] backdrop-blur-2xl dark:backdrop-brightness-50 bg-cover bg-center bg-no-repeat dark:bg-[#0a0a0a] text-[#1b1b18] font-poppins flex p-6 lg:p-8 items-center lg:justify-center min-h-screen flex-col relative">
        <div class="min-h-screen flex flex-col justify-between">
            {{-- Header --}}
            <header class="w-full pb-6 border-b border-neutral-200 dark:border-neutral-700">
                <div class="max-w-7xl mx-auto flex justify-between items-center sm:px-6">
                    <div class="flex items-center space-x-3 w-full max-w-56">
                        <x-app-logo-icon class="size-10 rounded-full fill-current text-indigo-600 dark:text-indigo-400" />
                        <span class="text-xl font-bold text-neutral-800 dark:text-neutral-200">SRPM</span>
                    </div>
                    <x-ui.theme-switcher variant="inline" />
                    <div class="space-x-4 w-full max-w-56 hidden sm:flex justify-end">
                        @auth
                        <a href="{{ route('home') }}" class="text-neutral-700 dark:text-neutral-300 hover:text-indigo-600 font-medium">Dashboard</a>
                        @endauth
                        @guest
                            <a href="{{ route('owner.auth.login') }}" class="text-neutral-700 dark:text-neutral-300 hover:text-indigo-600 font-medium">Owner Login</a>
                            <a href="{{ route('tenant.auth.login') }}" class="text-neutral-700 dark:text-neutral-300 hover:text-indigo-600 font-medium">Tenant Login</a>
                        @endguest
                    </div>
                </div>
            </header>

            {{-- Hero Section --}}
            <section class="flex flex-col items-center text-center px-6 py-16">
                <div class="flex flex-col items-center gap-4 mb-8">
                    <x-app-logo-icon class="size-28 fill-current text-indigo-600 dark:text-indigo-400 rounded-full" />
                    <h1 class="text-4xl md:text-5xl font-bold text-neutral-900 dark:text-neutral-100">
                        Smart Rental Property Management
                    </h1>
                    <p class="text-lg md:text-xl text-neutral-600 dark:text-neutral-300 max-w-2xl">
                        Streamline your rental operations with SRPM — an all-in-one solution for lease tracking, automated rent collection, and tenant management.
                    </p>
                </div>

                <div class="flex flex-wrap justify-center gap-4 mt-6">
                    <a href="{{ route('owner.auth.login') }}" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-neutral-200 font-semibold rounded-lg shadow hover:translate-y-[-3px] transition-transform duration-200 ease-in-out">
                        Login as Owner
                    </a>
                    <a href="{{ route('tenant.auth.login') }}" class="px-6 py-3 border dark:border-neutral-700 hover:bg-neutral-100 dark:bg-neutral-800 dark:hover:bg-neutral-700 text-neutral-900 dark:text-neutral-200 font-semibold rounded-lg shadow hover:translate-y-[-3px] transition-transform duration-200 ease-in-out">
                        Login as Tenant
                    </a>
                </div>
            </section>

            {{-- Features Section --}}
            <section class="py-16 border-t border-neutral-200 dark:border-neutral-700">
                <div class="max-w-6xl mx-auto px-6 text-center">
                    <h2 class="text-3xl font-bold text-neutral-900 dark:text-neutral-100 mb-12">Key Features</h2>

                    <div class="grid md:grid-cols-3 gap-8 text-neutral-600 dark:text-neutral-300">
                        <div class="p-6 rounded-xl bg-neutral-100 dark:bg-neutral-900 shadow-sm border dark:border-neutral-700 hover:scale-105 transition-transform duration-200 ease-in-out">
                            <h3 class="font-semibold text-lg text-indigo-600 dark:text-indigo-400 mb-2">Lease Tracking</h3>
                            <p>Monitor all lease agreements, start and end dates, and renewal reminders with ease.</p>
                        </div>

                        <div class="p-6 rounded-xl bg-neutral-100 dark:bg-neutral-900 shadow-sm border dark:border-neutral-700 hover:scale-105 transition-transform duration-200 ease-in-out">
                            <h3 class="font-semibold text-lg text-indigo-600 dark:text-indigo-400 mb-2">Automated Rent Collection</h3>
                            <p>Track rent payments, send reminders, and integrate GCash or e-wallet options.</p>
                        </div>

                        <div class="p-6 rounded-xl bg-neutral-100 dark:bg-neutral-900 shadow-sm border dark:border-neutral-700 hover:scale-105 transition-transform duration-200 ease-in-out">
                            <h3 class="font-semibold text-lg text-indigo-600 dark:text-indigo-400 mb-2">Maintenance Management</h3>
                            <p>Tenants can request repairs while owners manage and track maintenance updates.</p>
                        </div>
                    </div>
                </div>
            </section>

            {{-- Footer --}}
            <footer class="py-8 text-center border-t border-neutral-200 dark:border-neutral-700">
                <p class="text-neutral-600 dark:text-neutral-300 text-sm">
                    © {{ date('Y') }} SRPM — Smart Rental Property Management. All rights reserved.
                </p>
                <div class="flex justify-center gap-4 mt-3 text-sm">
                    <a href="{{ route('terms-and-conditions') }}" class="text-indigo-600 hover:underline">Terms & Conditions</a>
                </div>
            </footer>
        </div>
        <x-ui.toast position="top-center" maxToasts="3" progressBarVariant="thin" progressBarAlignment="top" />
        @livewireScriptConfig
    </body>
</html>
