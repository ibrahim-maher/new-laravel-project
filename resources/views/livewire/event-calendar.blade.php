<div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
    <div class="p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white">Event Calendar</h2>
            <div class="flex items-center space-x-2">
                <button wire:click="previousMonth" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                </button>
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ $monthName }}</h3>
                <button wire:click="nextMonth" class="inline-flex items-center p-2 border border-transparent rounded-full shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                    <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </button>
            </div>
        </div>

        <div class="grid grid-cols-7 gap-px border-b border-gray-200 dark:border-gray-700 mb-4">
            @foreach(['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'] as $dayName)
                <div class="text-center py-2 font-semibold text-gray-700 dark:text-gray-300">{{ $dayName }}</div>
            @endforeach
        </div>

        <div class="grid grid-cols-7 gap-px">
            @foreach($days as $day)
                <div class="{{ $day['isCurrentMonth'] ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-900 text-gray-400 dark:text-gray-500' }} 
                            {{ $day['isToday'] ?? false ? 'border-2 border-primary-500' : '' }} 
                            min-h-[100px] p-2 relative">
                    <div class="text-sm mb-1">{{ $day['day'] }}</div>
                    
                    @if(isset($day['events']) && count($day['events']) > 0)
                        <div class="space-y-1">
                            @foreach($day['events'] as $event)
                                <a href="{{ $event['url'] }}" class="block text-xs rounded-lg px-2 py-1 truncate {{ $event['is_active'] ? 'bg-primary-100 text-primary-800 dark:bg-primary-900 dark:text-primary-100' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300' }}">
                                    {{ $event['title'] }}
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
</div>