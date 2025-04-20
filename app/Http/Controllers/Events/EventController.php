<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use App\Models\RegistrationField;
use App\Models\Recurrence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EventController extends Controller
{
    /**
     * Display a listing of the events.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = Event::with(['venue', 'category']);

        // Apply search filter if present
        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $events = $query->paginate(20);
        
        return view('events.index', compact('events', 'search'));
    }

    /**
     * Show the form for creating a new event.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $this->authorize('create', Event::class);
        
        $venues = Venue::all();
        $categories = Category::all();
        
        return view('events.create', compact('venues', 'categories'));
    }

    /**
     * Store a newly created event in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->authorize('create', Event::class);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'venue_id' => 'required|exists:venues,id',
            'category_id' => 'required|exists:categories,id',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Set default for is_active
        $validated['is_active'] = $request->has('is_active');
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('event_logos', 'public');
            $validated['logo'] = $logoPath;
        }
        
        // Set created_by if event manager role
        if (Auth::check() && Auth::user()->role === 'EVENT_MANAGER') {
            $validated['created_by'] = Auth::id();
        }
        
        // Create the event
        $event = Event::create($validated);
        
        // Create default registration fields
        $this->createDefaultRegistrationFields($event);
        
        // If is_active, deactivate other events
        if ($validated['is_active']) {
            Event::where('id', '!=', $event->id)->update(['is_active' => false]);
        }
        
        // Handle recurrence if provided
        if ($request->has('is_recurring') && $request->has('recurrence_type')) {
            $this->saveRecurrencePattern($event, $request);
        }
        
        return redirect()->route('events.show', $event)
            ->with('success', 'Event created successfully.');
    }

    /**
     * Display the specified event.
     *
     * @param Event $event
     * @return \Illuminate\View\View
     */
    public function show(Event $event)
    {
        if (Auth::check() && Auth::user()->role === 'EVENT_MANAGER' && $event->created_by !== Auth::id()) {
            abort(403, "You don't have permission to view this event.");
        }
        
        return view('events.show', compact('event'));
    }

    /**
     * Show the form for editing the specified event.
     *
     * @param Event $event
     * @return \Illuminate\View\View
     */
    public function edit(Event $event)
    {
        $this->authorize('update', $event);
        
        $venues = Venue::all();
        $categories = Category::all();
        
        return view('events.edit', compact('event', 'venues', 'categories'));
    }

    /**
     * Update the specified event in storage.
     *
     * @param Request $request
     * @param Event $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'venue_id' => 'required|exists:venues,id',
            'category_id' => 'required|exists:categories,id',
            'is_active' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);
        
        // Set default for is_active
        $validated['is_active'] = $request->has('is_active');
        
        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($event->logo) {
                Storage::disk('public')->delete($event->logo);
            }
            
            $logoPath = $request->file('logo')->store('event_logos', 'public');
            $validated['logo'] = $logoPath;
        } elseif ($request->has('remove_logo') && $request->input('remove_logo')) {
            // Handle logo removal - ensure the remove_logo parameter is actually set to true
            if ($event->logo) {
                Storage::disk('public')->delete($event->logo);
            }
            $validated['logo'] = null;
        }
        
        // Update the event
        $event->update($validated);
        
        // If is_active, deactivate other events
        if ($validated['is_active']) {
            Event::where('id', '!=', $event->id)->update(['is_active' => false]);
        }
        
        // Handle recurrence if provided
        if ($request->has('is_recurring') && $request->has('recurrence_type')) {
            // First remove existing recurrences
            $event->recurrences()->delete();
            // Then save new pattern
            $this->saveRecurrencePattern($event, $request);
        } elseif ($request->has('is_recurring') && !$request->input('is_recurring')) {
            // If is_recurring is false, delete any existing recurrences
            $event->recurrences()->delete();
        }
        
        return redirect()->route('events.show', $event)
            ->with('success', 'Event updated successfully.');
    }

    /**
     * Remove the specified event from storage.
     *
     * @param Event $event
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);
        
        // Delete logo if exists
        if ($event->logo) {
            Storage::disk('public')->delete($event->logo);
        }
        
        // Delete related records
        $event->recurrences()->delete();
        $event->registrationFields()->delete();
        $event->ticketTypes()->delete();
        
        $event->delete();
        
        return redirect()->route('events.index')
            ->with('success', 'Event deleted successfully.');
    }

    /**
     * Create default registration fields for a new event.
     *
     * @param Event $event
     * @return void
     */
    private function createDefaultRegistrationFields(Event $event)
    {
        $defaultFields = [
            [
                'field_name' => 'First Name',
                'field_type' => 'text',
                'is_required' => true,
            ],
            [
                'field_name' => 'Last Name',
                'field_type' => 'text',
                'is_required' => true,
            ],
            [
                'field_name' => 'Email',
                'field_type' => 'email',
                'is_required' => true,
            ],
            [
                'field_name' => 'Phone',
                'field_type' => 'tel',
                'is_required' => true,
            ],
        ];
        
        foreach ($defaultFields as $field) {
            RegistrationField::create([
                'event_id' => $event->id,
                'field_name' => $field['field_name'],
                'field_type' => $field['field_type'],
                'is_required' => $field['is_required'],
            ]);
        }
    }

    /**
     * Save recurrence pattern for an event.
     *
     * @param Event $event
     * @param Request $request
     * @return void
     */
    private function saveRecurrencePattern(Event $event, Request $request)
    {
        $recurrenceType = $request->input('recurrence_type');
        $excludeDays = $request->input('exclude_days', []);
        
        Recurrence::create([
            'event_id' => $event->id,
            'recurrence_type' => $recurrenceType, // daily, weekly, monthly
            'exclude_days' => json_encode($excludeDays),
            'start_date' => $event->start_date,
            'end_date' => $request->input('recurrence_end_date', $event->end_date),
            'is_active' => true
        ]);
    }

    /**
     * Display calendar view of events.
     *
     * @return \Illuminate\View\View
     */
    public function calendar()
    {
        $events = Event::all();
        return view('events.calendar', compact('events'));
    }

    /**
     * Export events to CSV.
     *
     * @return \Illuminate\Http\Response
     */
    public function export()
    {
        $this->authorize('exportEvents');
        
        // Manual CSV export implementation
        $events = Event::with(['venue', 'category'])->get();
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="events.csv"',
        ];
        
        $callback = function() use ($events) {
            $file = fopen('php://output', 'w');
            
            // Add CSV headers
            fputcsv($file, ['ID', 'Name', 'Description', 'Start Date', 'End Date', 'Venue', 'Category', 'Is Active']);
            
            // Add event data
            foreach ($events as $event) {
                fputcsv($file, [
                    $event->id,
                    $event->name,
                    $event->description,
                    $event->start_date->format('Y-m-d H:i:s'),
                    $event->end_date->format('Y-m-d H:i:s'),
                    $event->venue ? $event->venue->name : '',
                    $event->category ? $event->category->name : '',
                    $event->is_active ? 'Yes' : 'No',
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import events from CSV.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function import(Request $request)
    {
        $this->authorize('importEvents');
        
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);
        
        try {
            $file = $request->file('csv_file');
            $path = $file->getRealPath();
            $data = array_map('str_getcsv', file($path));
            
            // Get headers from first row
            $headers = array_shift($data);
            
            // Define column mappings (header => db field)
            $mappings = [
                'Name' => 'name',
                'Description' => 'description',
                'Start Date' => 'start_date',
                'End Date' => 'end_date',
                'Venue ID' => 'venue_id',
                'Category ID' => 'category_id',
                'Is Active' => 'is_active',
            ];
            
            // Process rows
            foreach ($data as $row) {
                if (count($headers) !== count($row)) {
                    continue; // Skip rows with incorrect column count
                }
                
                $eventData = [];
                foreach ($headers as $index => $header) {
                    if (isset($mappings[$header]) && isset($row[$index])) {
                        $field = $mappings[$header];
                        $value = $row[$index];
                        
                        // Handle boolean field
                        if ($field === 'is_active') {
                            $eventData[$field] = in_array(strtolower($value), ['yes', 'true', '1']);
                            continue;
                        }
                        
                        $eventData[$field] = $value;
                    }
                }
                
                // Add created_by if user is EVENT_MANAGER
                if (Auth::user()->role === 'EVENT_MANAGER') {
                    $eventData['created_by'] = Auth::id();
                }
                
                // Create the event
                $event = Event::create($eventData);
                
                // Create default registration fields
                $this->createDefaultRegistrationFields($event);
            }
            
            return redirect()->route('events.index')
                ->with('success', 'Events imported successfully.');
        } catch (\Exception $e) {
            return redirect()->route('events.index')
                ->with('error', 'Error importing events: ' . $e->getMessage());
        }
    }
}