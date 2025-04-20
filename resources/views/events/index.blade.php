@extends('layouts.app')

@section('title', 'Events')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold text-gray-800">Events</h1>
        
        <div class="flex space-x-2">
            @can('create', App\Models\Event::class)
                <a href="{{ route('events.create') }}" class="px-4 py-2 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-400">
                    <i class="fa fa-plus mr-2"></i> Create Event
                </a>
            @endcan
            
            <a href="{{ route('events.calendar') }}" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <i class="fa fa-calendar mr-2"></i> Calendar View
            </a>
            
            @can('exportEvents')
                <a href="{{ route('events.export') }}" class="px-4 py-2 bg-purple-600 text-white font-semibold rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-400">
                    <i class="fa fa-download mr-2"></i> Export CSV
                </a>
            @endcan
            
            @can('importEvents')
                <button type="button" onclick="document.getElementById('import-modal').classList.remove('hidden')" class="px-4 py-2 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400">
                    <i class="fa fa-upload mr-2"></i> Import CSV
                </button>
            @endcan
        </div>
    </div>

    <!-- Search & Filter Section -->
    <div class="bg-white rounded-lg shadow-md p-6 mb-6">
        <form action="{{ route('events.index') }}" method="GET" class="flex flex-col md:flex-row md:items-end space-y-4 md:space-y-0 md:space-x-4">
            <div class="flex-grow">
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search Events</label>
                <input type="text" name="search" id="search" value="{{ request('search') }}" placeholder="Search by name..." class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
            </div>

            @if(isset($categories) && isset($venues))
            <div class="w-full md:w-48">
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select name="category_id" id="category_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Categories</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ request('category_id') == $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full md:w-48">
                <label for="venue_id" class="block text-sm font-medium text-gray-700 mb-1">Venue</label>
                <select name="venue_id" id="venue_id" class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                    <option value="">All Venues</option>
                    @foreach($venues as $venue)
                        <option value="{{ $venue->id }}" {{ request('venue_id') == $venue->id ? 'selected' : '' }}>
                            {{ $venue->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="flex space-x-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-400">
                    <i class="fa fa-search mr-2"></i> Search
                </button>
                
                <a href="{{ route('events.index') }}" class="px-4 py-2 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400">
                    <i class="fa fa-times mr-2"></i> Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Active Event Banner (if any) -->
    @foreach($events as $event)
        @if($event->is_active)
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 shadow-md">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fa fa-check-circle text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium">
                            Currently Active Event: <span class="font-bold">{{ $event->name }}</span>
                        </p>
                        <p class="text-xs">
                            {{ $event->start_date->format('M d, Y H:i') }} - {{ $event->end_date->format('M d, Y H:i') }}
                        </p>
                    </div>
                    <div class="ml-auto">
                        <a href="{{ route('events.show', $event) }}" class="text-green-600 hover:text-green-800">
                            <i class="fa fa-eye mr-1"></i> View Details
                        </a>
                    </div>
                </div>
            </div>
            @break
        @endif
    @endforeach

    <!-- Events Table -->
    <div class="bg-white rounded-lg shadow-md overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Venue</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($events as $event)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                @if($event->image)
                                    <div class="flex-shrink-0 h-10 w-10 mr-3">
                                        <img class="h-10 w-10 rounded-full object-cover" src="{{ asset('storage/' . $event->image) }}" alt="{{ $event->name }}">
                                    </div>
                                @else
                                    <div class="flex-shrink-0 h-10 w-10 mr-3 bg-gray-200 rounded-full flex items-center justify-center">
                                        <i class="fa fa-calendar-alt text-gray-500"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $event->name }}</div>
                                    <div class="text-xs text-gray-500">{{ Str::limit($event->description, 50) }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $event->start_date->format('M d, Y') }}</div>
                            <div class="text-xs text-gray-500">{{ $event->start_date->format('H:i') }} - {{ $event->end_date->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ $event->venue->name ?? 'N/A' }}</div>
                            <div class="text-xs text-gray-500">{{ $event->venue->address ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ $event->category->name ?? 'Uncategorized' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($event->is_active)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            @elseif($event->start_date->isFuture())
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Upcoming
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                    Past
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <a href="{{ route('events.show', $event) }}" class="text-blue-600 hover:text-blue-900" title="View">
                                    <i class="fa fa-eye"></i>
                                </a>
                                
                                @can('update', $event)
                                <a href="{{ route('events.edit', $event) }}" class="text-indigo-600 hover:text-indigo-900" title="Edit">
                                    <i class="fa fa-edit"></i>
                                </a>
                                @endcan
                                
                                @can('delete', $event)
                                <form action="{{ route('events.destroy', $event) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this event?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900" title="Delete">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                            No events found. 
                            @can('create', App\Models\Event::class)
                                <a href="{{ route('events.create') }}" class="text-blue-600 hover:text-blue-900">Create your first event</a>
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="mt-4">
        {{ $events->links() }}
    </div>
    
    <!-- Import Modal -->
    <div id="import-modal" class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg shadow-xl p-6 w-full max-w-md">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">Import Events</h3>
                <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-500">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            
            <form action="{{ route('events.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label for="import_file" class="block text-sm font-medium text-gray-700 mb-1">CSV File</label>
                    <input type="file" name="import_file" id="import_file" accept=".csv" class="w-full border border-gray-300 rounded-lg p-2">
                    <p class="text-xs text-gray-500 mt-1">Please upload a CSV file with the correct format.</p>
                </div>
                
                <div class="flex justify-end space-x-2">
                    <button type="button" onclick="document.getElementById('import-modal').classList.add('hidden')" class="px-4 py-2 bg-gray-500 text-white font-semibold rounded-lg hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-teal-600 text-white font-semibold rounded-lg hover:bg-teal-700 focus:outline-none focus:ring-2 focus:ring-teal-400">
                        <i class="fa fa-upload mr-2"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection