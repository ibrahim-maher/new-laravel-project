<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="md:col-span-2 bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-6">Check-in Scanner</h2>
            
            @if($error)
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded" role="alert">
                    <p>{{ $error }}</p>
                </div>
            @endif
            
            @if($success)
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded" role="alert">
                    <p>{{ $success }}</p>
                </div>
            @endif
            
            <div class="space-y-6">
                <!-- Filter by event -->
                <div>
                    <label for="event" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Filter by Event</label>
                    <select id="event" wire:model="selectedEvent" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm rounded-md dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="">All Events</option>
                        @foreach($events as $event)
                            <option value="{{ $event->id }}">{{ $event->name }}</option>
                        @endforeach
                    </select>
                </div>
            
                <!-- Scan QR Code -->
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Scan QR Code</h3>
                    <div class="flex">
                        <input type="text" wire:model.defer="scannedCode" class="flex-1 shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-white" placeholder="Scan QR code...">
                        <button wire:click="processCode" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Scan
                        </button>
                    </div>
                </div>
                
                <!-- Manual Entry -->
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Manual Entry</h3>
                    <div class="flex">
                        <input type="text" wire:model.defer="manualRegistrationCode" class="flex-1 shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-white" placeholder="Registration ID or Number...">
                        <button wire:click="processManualCode" class="ml-3 inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                            Submit
                        </button>
                    </div>
                </div>
                
                <!-- Search -->
                <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Search Attendee</h3>
                    <div class="relative">
                        <input type="text" wire:model="searchQuery" wire:keyup="search" class="shadow-sm focus:ring-primary-500 focus:border-primary-500 block w-full sm:text-sm border-gray-300 rounded-md dark:bg-gray-800 dark:border-gray-600 dark:text-white" placeholder="Search by name or email...">
                        
                        @if(count($searchResults) > 0)
                            <div class="absolute z-10 mt-1 w-full bg-white dark:bg-gray-800 shadow-lg rounded-md overflow-hidden">
                                <ul class="max-h-60 overflow-y-auto">
                                    @foreach($searchResults as $result)
                                        <li>
                                            <button wire:click="selectRegistration({{ $result->id }})" class="w-full text-left px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <div class="font-medium text-gray-900 dark:text-white">{{ $result->user->name }}</div>
                                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $result->registration_number }} - {{ $result->event->name }}</div>
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Recent Check-ins</h2>
            
            <div class="space-y-4">
                @forelse($recentCheckins as $checkin)
                    <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <span class="inline-flex items-center justify-center h-10 w-10 rounded-full bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-100">
                                    <span class="text-xl font-medium leading-none">{{ substr($checkin->registration->user->name ?? 'U', 0, 1) }}</span>
                                </span>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $checkin->registration->user->name ?? 'Unknown' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $checkin->registration->event->name ?? 'Unknown Event' }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $checkin->check_in_time->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">No recent check-ins</div>
                @endforelse
            </div>
            
            <div class="mt-4">
                <a href="{{ route('checkin.logs') }}" class="text-sm font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                    View all check-ins →
                </a>
            </div>
        </div>
    </div>
</div>