<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - @yield('title', 'Dashboard')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="font-sans antialiased bg-gray-100 dark:bg-gray-900">
    <div class="min-h-screen flex">
        <!-- Sidebar -->
        <div class="hidden lg:flex lg:flex-col lg:w-64 lg:fixed lg:inset-y-0 lg:border-r lg:border-gray-200 lg:bg-white lg:dark:bg-gray-800 lg:dark:border-gray-700">
            <div class="flex flex-col flex-grow pt-5 pb-4 overflow-y-auto">
                <div class="flex items-center justify-center flex-shrink-0 px-4">
                    <span class="text-xl font-bold text-gray-800 dark:text-white">EventsManager</span>
                </div>
                <nav class="mt-5 flex-1 px-2 space-y-1">
                    <a href="{{ route('dashboard') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('dashboard') ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <svg class="mr-3 h-6 w-6 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                        </svg>
                        Dashboard
                    </a>
                    <a href="{{ route('events.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('events.*') ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <svg class="mr-3 h-6 w-6 {{ request()->routeIs('events.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Events
                    </a>
                    <a href="{{ route('registrations.index') }}" class="group flex items-center px-2 py-2 text-sm font-medium rounded-md {{ request()->routeIs('registrations.*') ? 'bg-primary-500 text-white' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700' }}">
                        <svg class="mr-3 h-6 w-6 {{ request()->routeIs('registrations.*') ? 'text-white' : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300' }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        Registrations
                    </a>
                    <!-- Add more navigation items here -->
                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:pl-64 flex flex-col flex-1">
            <!-- Top navbar -->
            <div class="sticky top-0 z-10 flex-shrink-0 flex h-16 bg-white dark:bg-gray-800 shadow">
                <button type="button" class="lg:hidden px-4 text-gray-500 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500">
                    <span class="sr-only">Open sidebar</span>
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
                <div class="flex-1 px-4 flex justify-between">
                    <div class="flex-1 flex">
                        <div class="w-full flex md:ml-0">
                            <label for="search" class="sr-only">Search</label>
                            <div class="relative w-full text-gray-400 focus-within:text-gray-600">
                                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                                <input id="search" class="block w-full h-full pl-10 pr-3 py-2 border-transparent text-gray-900 placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-0 focus:border-transparent sm:text-sm" placeholder="Search" type="search">
                            </div>
                        </div>
                    </div>
                    <div class="ml-4 flex items-center md:ml-6">
                        <!-- Profile dropdown -->
                        <div class="ml-3 relative">
                            <div>
                                <button type="button" class="max-w-xs bg-white flex items-center text-sm rounded-full focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500" id="user-menu-button" aria-expanded="false" aria-haspopup="true">
                                    <span class="sr-only">Open user menu</span>
                                    <img class="h-8 w-8 rounded-full" src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name) }}&background=random" alt="">
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <main class="flex-1">
                <div class="py-6">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        <div class="flex items-center justify-between mb-6">
                            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">@yield('header', 'Dashboard')</h1>
                            @yield('actions')
                        </div>
                    </div>
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
                        @if(session('success'))
                            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
                                <p>{{ session('success') }}</p>
                            </div>
                        @endif
                        
                        @if(session('error'))
                            <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                                <p>{{ session('error') }}</p>
                            </div>
                        @endif
                    
                        @yield('content')
                    </div>
                </div>
            </main>
        </div>
    </div>
    @livewireScripts
</body>
</html>