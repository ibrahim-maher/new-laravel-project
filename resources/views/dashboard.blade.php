@extends('layouts.admin')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
        <div class="p-6 bg-white dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-primary-100 dark:bg-primary-900 rounded-md p-3">
                    <svg class="h-8 w-8 text-primary-600 dark:text-primary-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Total Events
                        </dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ \App\Models\Event::count() }}
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
        <div class="p-6 bg-white dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-green-100 dark:bg-green-900 rounded-md p-3">
                    <svg class="h-8 w-8 text-green-600 dark:text-green-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Total Registrations
                        </dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ \App\Models\Registration::count() }}
                            </div>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg">
        <div class="p-6 bg-white dark:bg-gray-800">
            <div class="flex items-center">
                <div class="flex-shrink-0 bg-yellow-100 dark:bg-yellow-900 rounded-md p-3">
                    <svg class="h-8 w-8 text-yellow-600 dark:text-yellow-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="ml-5 w-0 flex-1">
                    <dl>
                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400 truncate">
                            Check-ins Today
                        </dt>
                        <dd>
                            <div class="text-lg font-medium text-gray-900 dark:text-white">
                                {{ \App\Models\Checkin::whereDate('created_at', today())->count() }}
                            </div>
                        </dd>
                    </dl>
                </div>