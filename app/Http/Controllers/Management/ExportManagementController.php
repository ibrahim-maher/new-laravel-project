<?php

namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Checkin;
use App\Models\TicketType;
use App\Models\Venue;
use App\Models\Category;
use App\Models\RegistrationField;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExportManagementController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,event_manager');
    }

    /**
     * Display the export/import interface
     */
    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user->hasRole('admin');
        
        // Get events the user has access to
        if ($isAdmin) {
            $events = Event::with(['venue', 'category'])->get();
        } else {
            $events = Event::where('created_by', $user->id)
                ->with(['venue', 'category'])
                ->get();
        }
        
        return view('management.export', compact('events', 'isAdmin'));
    }

    /**
     * Export data to various formats
     */
    public function export(Request $request, $type)
    {
        $format = $request->input('format', 'csv'); // Default to CSV since we're not using PhpSpreadsheet
        $user = Auth::user();
        
        // Check if user has permission to export this data type
        if (!$user->hasRole('admin') && $type != 'my_events' && $type != 'my_registrations') {
            return back()->with('error', 'You do not have permission to export this data.');
        }
        
        // Only support CSV format
        if ($format !== 'csv') {
            return back()->with('error', 'Only CSV format is supported. Please install the PhpSpreadsheet library for XLSX support.');
        }
        
        switch ($type) {
            case 'events':
                return $this->exportEvents(null, $format);
                
            case 'my_events':
                return $this->exportEvents($user->id, $format);
                
            case 'registrations':
                $eventId = $request->input('event_id');
                return $this->exportRegistrations($eventId, null, $format);
                
            case 'my_registrations':
                $eventIds = Event::where('created_by', $user->id)->pluck('id')->toArray();
                return $this->exportRegistrations(null, $eventIds, $format);
                
            case 'checkins':
                $eventId = $request->input('event_id');
                $dateRange = $request->input('date_range');
                return $this->exportCheckins($eventId, $dateRange, $format);
                
            default:
                return back()->with('error', 'Invalid export type.');
        }
    }

    /**
     * Import data from uploaded file
     */
    public function import(Request $request, $type)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);
        
        $user = Auth::user();
        
        // Check if user has permission to import this data type
        if (!$user->hasRole('admin') && $type != 'my_events') {
            return back()->with('error', 'You do not have permission to import this data.');
        }
        
        try {
            switch ($type) {
                case 'events':
                case 'my_events':
                    return $this->importEvents($request->file('file'), $user->id);
                    
                case 'registrations':
                    $eventId = $request->input('event_id');
                    return $this->importRegistrations($request->file('file'), $eventId);
                    
                default:
                    return back()->with('error', 'Invalid import type.');
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }

    /**
     * Export events data
     */
    protected function exportEvents($userId = null, $format = 'csv')
    {
        // Build the query
        $query = Event::with(['venue', 'category', 'createdBy']);
        
        // Filter by user if specified
        if ($userId) {
            $query->where('created_by', $userId);
        }
        
        $events = $query->get();
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        $file = fopen($tempFile, 'w');
        
        // Add headers
        fputcsv($file, [
            'ID', 'Name', 'Description', 'Start Date', 'End Date', 
            'Venue', 'Category', 'Created By', 'Is Active', 'Created At'
        ]);
        
        // Add data
        foreach ($events as $event) {
            fputcsv($file, [
                $event->id,
                $event->name,
                $event->description,
                $event->start_date->format('Y-m-d H:i:s'),
                $event->end_date->format('Y-m-d H:i:s'),
                $event->venue ? $event->venue->name : '',
                $event->category ? $event->category->name : '',
                $event->createdBy ? $event->createdBy->name : '',
                $event->is_active ? 'Yes' : 'No',
                $event->created_at->format('Y-m-d H:i:s'),
            ]);
        }
        
        fclose($file);
        
        // Generate filename
        $filename = 'events.csv';
        if ($userId) {
            $filename = 'my_events.csv';
        }
        
        return Response::download($tempFile, $filename, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export registrations data
     */
    protected function exportRegistrations($eventId = null, $eventIds = null, $format = 'csv')
    {
        // Build the query
        $query = Registration::with(['event', 'ticketType']);
        
        // Filter by event if specified
        if ($eventId) {
            $query->where('event_id', $eventId);
        } elseif ($eventIds) {
            $query->whereIn('event_id', $eventIds);
        }
        
        $registrations = $query->get();
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        $file = fopen($tempFile, 'w');
        
        // Add headers
        fputcsv($file, [
            'ID', 'Event', 'Registration Number', 'Ticket Type', 
            'First Name', 'Last Name', 'Email', 'Phone', 
            'Amount Paid', 'Registered At', 'Check-in Status', 'Last Check-in'
        ]);
        
        // Add data
        foreach ($registrations as $registration) {
            // Get custom registration data
            $registrationData = json_decode($registration->registration_data, true) ?? [];
            $firstName = $registrationData['First Name'] ?? '';
            $lastName = $registrationData['Last Name'] ?? '';
            $email = $registrationData['Email'] ?? '';
            $phone = $registrationData['Phone'] ?? '';
            
            fputcsv($file, [
                $registration->id,
                $registration->event->name ?? '',
                $registration->registration_number,
                $registration->ticketType->name ?? '',
                $firstName,
                $lastName,
                $email,
                $phone,
                $registration->amount_paid,
                $registration->created_at->format('Y-m-d H:i:s'),
                $registration->checked_in ? 'Checked In' : 'Not Checked In',
                $registration->last_checkin_at ? 
                    $registration->last_checkin_at->format('Y-m-d H:i:s') : '',
            ]);
        }
        
        fclose($file);
        
        // Generate filename
        $filename = 'registrations.csv';
        if ($eventIds) {
            $filename = 'my_registrations.csv';
        } elseif ($eventId) {
            $event = Event::find($eventId);
            if ($event) {
                $filename = Str::slug($event->name) . '_registrations.csv';
            }
        }
        
        return Response::download($tempFile, $filename, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Export check-ins data
     */
    protected function exportCheckins($eventId = null, $dateRange = null, $format = 'csv')
    {
        // Build the query
        $query = Checkin::with(['registration', 'registration.event', 'scannedBy']);
        
        // Filter by event if specified
        if ($eventId) {
            $query->whereHas('registration', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            });
        }
        
        // Filter by date range if specified
        if ($dateRange) {
            $dates = explode(',', $dateRange);
            if (count($dates) == 2) {
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = Carbon::parse($dates[1])->endOfDay();
                $query->whereBetween('created_at', [$startDate, $endDate]);
            }
        }
        
        $checkins = $query->get();
        
        // Create temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'export_');
        $file = fopen($tempFile, 'w');
        
        // Add headers
        fputcsv($file, [
            'ID', 'Event', 'Registration Number', 'Attendee Name', 'Attendee Email',
            'Check-in Time', 'Check-in Type', 'Scanned By', 'Duration (minutes)'
        ]);
        
        // Add data
        foreach ($checkins as $checkin) {
            // Get registration data
            $registration = $checkin->registration;
            $registrationData = $registration ? json_decode($registration->registration_data, true) ?? [] : [];
            $firstName = $registrationData['First Name'] ?? '';
            $lastName = $registrationData['Last Name'] ?? '';
            $fullName = trim("$firstName $lastName");
            $email = $registrationData['Email'] ?? '';
            
            // Calculate duration if check-out exists
            $duration = null;
            if ($checkin->check_out_time) {
                $checkIn = Carbon::parse($checkin->created_at);
                $checkOut = Carbon::parse($checkin->check_out_time);
                $duration = $checkOut->diffInMinutes($checkIn);
            }
            
            fputcsv($file, [
                $checkin->id,
                $registration && $registration->event ? $registration->event->name : '',
                $registration ? $registration->registration_number : '',
                $fullName,
                $email,
                $checkin->created_at->format('Y-m-d H:i:s'),
                $checkin->check_type,
                $checkin->scannedBy ? $checkin->scannedBy->name : '',
                $duration,
            ]);
        }
        
        fclose($file);
        
        // Generate filename
        $filename = 'checkins.csv';
        if ($eventId) {
            $event = Event::find($eventId);
            if ($event) {
                $filename = Str::slug($event->name) . '_checkins.csv';
            }
        }
        
        return Response::download($tempFile, $filename, [
            'Content-Type' => 'text/csv',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Import events from file
     */
    protected function importEvents($file, $userId = null)
    {
        // Get the CSV file contents
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        // Get the headers from the first row
        $headers = array_shift($data);
        
        // Map headers to their positions
        $headerMap = array_flip(array_map('strtolower', $headers));
        
        // Counter for created events
        $createdCount = 0;
        
        // Process each row
        foreach ($data as $rowData) {
            // Skip empty rows
            if (empty(array_filter($rowData))) {
                continue;
            }
            
            // Make sure the row data has the same number of columns as headers
            if (count($rowData) !== count($headers)) {
                continue;
            }
            
            // Map data to fields
            $eventData = [];
            
            // Required fields
            if (isset($headerMap['name']) && isset($rowData[$headerMap['name']])) {
                $eventData['name'] = $rowData[$headerMap['name']];
            } else {
                continue; // Skip row if name is missing
            }
            
            // Optional fields
            if (isset($headerMap['description']) && isset($rowData[$headerMap['description']])) {
                $eventData['description'] = $rowData[$headerMap['description']];
            } else {
                $eventData['description'] = '';
            }
            
            // Dates
            if (isset($headerMap['start date']) && isset($rowData[$headerMap['start date']])) {
                $eventData['start_date'] = Carbon::parse($rowData[$headerMap['start date']]);
            } else {
                $eventData['start_date'] = now();
            }
            
            if (isset($headerMap['end date']) && isset($rowData[$headerMap['end date']])) {
                $eventData['end_date'] = Carbon::parse($rowData[$headerMap['end date']]);
            } else {
                $eventData['end_date'] = now()->addHours(1);
            }
            
            // Venue
            $venueId = null;
            if (isset($headerMap['venue id']) && isset($rowData[$headerMap['venue id']])) {
                $venueId = $rowData[$headerMap['venue id']];
            } elseif (isset($headerMap['venue']) && isset($rowData[$headerMap['venue']])) {
                $venueName = $rowData[$headerMap['venue']];
                $venue = Venue::where('name', $venueName)->first();
                if ($venue) {
                    $venueId = $venue->id;
                }
            }
            $eventData['venue_id'] = $venueId;
            
            // Category
            $categoryId = null;
            if (isset($headerMap['category id']) && isset($rowData[$headerMap['category id']])) {
                $categoryId = $rowData[$headerMap['category id']];
            } elseif (isset($headerMap['category']) && isset($rowData[$headerMap['category']])) {
                $categoryName = $rowData[$headerMap['category']];
                $category = Category::where('name', $categoryName)->first();
                if ($category) {
                    $categoryId = $category->id;
                }
            }
            $eventData['category_id'] = $categoryId;
            
            // Is active
            if (isset($headerMap['is active']) && isset($rowData[$headerMap['is active']])) {
                $isActive = $rowData[$headerMap['is active']];
                $eventData['is_active'] = in_array(strtolower($isActive), ['yes', 'true', '1', 'y', 'active']);
            } else {
                $eventData['is_active'] = false;
            }
            
            // Created by
            $eventData['created_by'] = $userId;
            
            // Create the event
            $event = Event::create($eventData);
            
            // Create default registration fields
            $this->createDefaultRegistrationFields($event);
            
            $createdCount++;
        }
        
        return redirect()->route('management.export.index')
            ->with('success', "{$createdCount} events imported successfully.");
    }
    
    /**
     * Import registrations from file
     */
    protected function importRegistrations($file, $eventId = null)
    {
        // Check if event exists
        if ($eventId) {
            $event = Event::find($eventId);
            if (!$event) {
                return redirect()->route('management.export.index')
                    ->with('error', 'Event not found.');
            }
        }
        
        // Get the CSV file contents
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        
        // Get the headers from the first row
        $headers = array_shift($data);
        
        // Map headers to their positions
        $headerMap = array_flip(array_map('strtolower', $headers));
        
        // Counter for created registrations
        $createdCount = 0;
        
        // Process each row
        foreach ($data as $rowData) {
            // Skip empty rows
            if (empty(array_filter($rowData))) {
                continue;
            }
            
            // Make sure the row data has the same number of columns as headers
            if (count($rowData) !== count($headers)) {
                continue;
            }
            
            // Determine event ID if not provided
            $regEventId = $eventId;
            if (!$regEventId && isset($headerMap['event']) && isset($rowData[$headerMap['event']])) {
                $eventName = $rowData[$headerMap['event']];
                $event = Event::where('name', $eventName)->first();
                if ($event) {
                    $regEventId = $event->id;
                }
            }
            
            // Skip row if no event could be determined
            if (!$regEventId) {
                continue;
            }
            
            // Get ticket type if specified
            $ticketTypeId = null;
            if (isset($headerMap['ticket type']) && isset($rowData[$headerMap['ticket type']])) {
                $ticketTypeName = $rowData[$headerMap['ticket type']];
                $ticketType = TicketType::where('event_id', $regEventId)
                    ->where('name', $ticketTypeName)
                    ->first();
                if ($ticketType) {
                    $ticketTypeId = $ticketType->id;
                }
            }
            
            // Create registration data JSON from available fields
            $registrationData = [];
            
            // Standard fields
            if (isset($headerMap['first name']) && isset($rowData[$headerMap['first name']])) {
                $registrationData['First Name'] = $rowData[$headerMap['first name']];
            }
            
            if (isset($headerMap['last name']) && isset($rowData[$headerMap['last name']])) {
                $registrationData['Last Name'] = $rowData[$headerMap['last name']];
            }
            
            if (isset($headerMap['email']) && isset($rowData[$headerMap['email']])) {
                $registrationData['Email'] = $rowData[$headerMap['email']];
            }
            
            if (isset($headerMap['phone']) && isset($rowData[$headerMap['phone']])) {
                $registrationData['Phone'] = $rowData[$headerMap['phone']];
            }
            
            // Add any custom fields that might be in the import
            foreach ($headerMap as $header => $index) {
                if (!in_array($header, ['id', 'event', 'event_id', 'ticket type', 'ticket_type_id', 
                    'first name', 'last name', 'email', 'phone', 'amount paid', 
                    'registration number', 'checked in', 'last check-in'])) {
                    $registrationData[ucwords($header)] = $rowData[$index] ?? '';
                }
            }
            
            // Generate registration number if not provided
            $registrationNumber = null;
            if (isset($headerMap['registration number']) && isset($rowData[$headerMap['registration number']])) {
                $registrationNumber = $rowData[$headerMap['registration number']];
            }
            
            if (!$registrationNumber) {
                $registrationNumber = 'REG-' . strtoupper(Str::random(8));
            }
            
            // Parse amount paid
            $amountPaid = 0;
            if (isset($headerMap['amount paid']) && isset($rowData[$headerMap['amount paid']])) {
                $amountPaid = is_numeric($rowData[$headerMap['amount paid']]) ? 
                    $rowData[$headerMap['amount paid']] : 0;
            }
            
            // Parse checked in status
            $checkedIn = false;
            if (isset($headerMap['checked in']) && isset($rowData[$headerMap['checked in']])) {
                $checkedIn = in_array(strtolower($rowData[$headerMap['checked in']]), 
                    ['yes', 'true', '1', 'y', 'checked in']);
            }
            
            // Parse last check-in time
            $lastCheckinAt = null;
            if (isset($headerMap['last check-in']) && isset($rowData[$headerMap['last check-in']])) {
                $lastCheckin = $rowData[$headerMap['last check-in']];
                if (!empty($lastCheckin)) {
                    try {
                        $lastCheckinAt = Carbon::parse($lastCheckin);
                    } catch (\Exception $e) {
                        // Ignore parsing errors
                    }
                }
            }
            
            // Create the registration
            Registration::create([
                'event_id' => $regEventId,
                'ticket_type_id' => $ticketTypeId,
                'registration_number' => $registrationNumber,
                'registration_data' => json_encode($registrationData),
                'amount_paid' => $amountPaid,
                'checked_in' => $checkedIn,
                'last_checkin_at' => $lastCheckinAt,
            ]);
            
            $createdCount++;
        }
        
        return redirect()->route('management.export.index')
            ->with('success', "{$createdCount} registrations imported successfully.");
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
}