<div>
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
        <div class="p-6 bg-white dark:bg-gray-800">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-3 md:space-y-0 md:space-x-4 mb-6">
                <div class="w-full md:w-2/3">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                            <svg class="w-5 h-5 text-gray-500 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" wire:model.debounce.300ms="search" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full pl-10 p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500" placeholder="Search events...">
                    </div>
                </div>
                <div class="w-full md:w-1/3 flex flex-col md:flex-row space-y-2 md:space-y-0 md:space-x-2">
                    <select wire:model="categoryFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <select wire:model="statusFilter" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-primary-500 focus:border-primary-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-primary-500 dark:focus:border-primary-500">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="past">Past</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 cursor-pointer" wire:click="sortBy('name')">
                                Event Name
                                @if ($sortField === 'name')
                                    <span class="ml-1">
                                        @if ($sortDirection === 'asc')
                                            &uarr;
                                        @else
                                            &darr;
                                        @endif
                                    </span>
                                @endif
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400 cursor-pointer" wire:click="sortBy('start_date')">
                                Date
                                @if ($sortField === 'start_date')
                                    <span class="ml-1">
                                        @if ($sortDirection === 'asc')
                                            &uarr;
                                        @else
                                            &darr;
                                        @endif
                                    </span>
                                @endif
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Venue
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Category
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase dark:text-gray-400">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        @forelse ($events as $event)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            @if ($event->logo)
                                                <img class="h-10 w-10 rounded-full object-cover" src="{{ Storage::url($event->logo) }}" alt="{{ $event->name }}">
                                            @else
                                                <div class="h-10 w-10 rounded-full bg-primary-100 flex items-center justify-center">
                                                    <span class="text-primary-800 font-bold">{{ substr($event->name, 0, 1) }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $event->name }}
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                {{ Str::limit($event->description, 50) }}
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $event->start_date->format('M d, Y') }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $event->start_date->format('H:i') }} - {{ $event->end_date->format('H:i') }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900 dark:text-white">{{ $event->venue->name ?? 'N/A' }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $event->venue->address ?? '' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-primary-100 text-primary-800 dark:bg-primary-700 dark:text-primary-100">
                                        {{ $event->category->name ?? 'Uncategorized' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($event->is_active)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-700 dark:text-green-100">
                                            Active
                                        </span>
                                    @elseif($event->start_date->isFuture())
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 dark:bg-yellow-700 dark:text-yellow-100">
                                            Upcoming
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                            Past
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('events.show', $event) }}" class="text-primary-600 hover:text-primary-900 dark:text-primary-400 dark:hover:text-primary-300 mr-3">
                                        View
                                    </a>
                                    <a href="{{ route('events.edit', $event) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">
                                        Edit
                                    </a>
                                    <button onclick="confirm('Are you sure you want to delete this event?') || event.stopImmediatePropagation()" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                        Delete
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                                    No events found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4">
                {{ $events->links() }}
            </div>
        </div>
    </div>
</div>