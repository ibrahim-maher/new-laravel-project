
namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\RegistrationField;
use App\Services\QrCodeService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use App\Http\Requests\Registration\StoreRegistrationRequest;
use App\Http\Requests\Registration\UpdateRegistrationRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationController extends Controller
{
    protected $qrCodeService;
    protected $notificationService;

    public function __construct(QrCodeService $qrCodeService, NotificationService $notificationService)
    {
        $this->middleware('auth')->except(['create', 'store', 'payment', 'confirmPayment']);
        $this->qrCodeService = $qrCodeService;
        $this->notificationService = $notificationService;
    }

    /**
     * Display a listing of registrations for an event
     */
    public function index(Event $event)
    {
        $this->authorize('viewAny', Registration::class);
        
        $registrations = $event->registrations()
            ->with('tickets')
            ->latest('created_at')
            ->paginate(15);
        
        return view('registration.index', compact('event', 'registrations'));
    }

    /**
     * Show the form for creating a new registration
     */
    public function create(Event $event)
    {
        // Check if event is past or registration closed
        if ($event->start_date < now()) {
            return redirect()->route('events.show', $event)
                ->with('error', 'Registration is closed for this event.');
        }

        // Get custom fields
        $customFields = RegistrationField::where('event_id', $event->id)->orderBy('order')->get();
        
        return view('registration.create', compact('event', 'customFields'));
    }

    /**
     * Store a newly created registration
     */
    public function store(StoreRegistrationRequest $request, Event $event)
    {
        // Check if registration is closed
        if ($event->start_date < now()) {
            return redirect()->route('events.show', $event)
                ->with('error', 'Registration is closed for this event.');
        }

        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Create registration
            $registration = new Registration([
                'event_id' => $event->id,
                'user_id' => Auth::id() ?? null,
                'status' => 'pending',
                'payment_status' => $event->is_free ? 'paid' : 'unpaid',
                'custom_fields' => $request->custom_fields ?? null,
            ]);
            
            $registration->save();

            // Handle tickets
            if ($event->is_free) {
                // Create free ticket
                $ticket = new Ticket([
                    'registration_id' => $registration->id,
                    'ticket_type_id' => TicketType::where('event_id', $event->id)
                                          ->where('is_active', true)
                                          ->first()->id ?? null,
                    'ticket_number' => Str::uuid(),
                    'qr_code' => $this->qrCodeService->generateTicketQR($registration->id),
                    'price' => 0.00,
                    'status' => 'valid',
                ]);
                
                $ticket->save();
                
                // Send confirmation and ticket
                $this->notificationService->sendRegistrationConfirmation($registration);
                
                DB::commit();
                
                return redirect()->route('registration.tickets.show', ['registration' => $registration, 'ticket' => $ticket])
                    ->with('success', 'Registration completed successfully!');
            } else {
                // For paid events, redirect to payment
                DB::commit();
                
                return redirect()->route('registration.payment', $registration);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Registration failed: ' . $e->getMessage());
        }
    }

    /**
     * Show payment page for a registration
     */
    public function payment(Registration $registration)
    {
        // Check if registration belongs to current user or is an admin
        if (!Auth::check() || (!Auth::user()->can('manage', Registration::class) && $registration->user_id != Auth::id())) {
            abort(403);
        }

        // If already paid, redirect to tickets
        if ($registration->payment_status == 'paid') {
            return redirect()->route('registration.show', $registration)
                ->with('info', 'This registration has already been paid for.');
        }

        // Get ticket types for the event
        $ticketTypes = $registration->event->ticketTypes()
            ->where('is_active', true)
            ->get();
        
        return view('registration.payment', compact('registration', 'ticketTypes'));
    }

    /**
     * Confirm payment for a registration
     */
    public function confirmPayment(Request $request, Registration $registration)
    {
        // Process payment
        // This would typically include integration with a payment gateway like Stripe or PayPal
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Update registration status
            $registration->payment_status = 'paid';
            $registration->payment_method = $request->payment_method;
            $registration->payment_id = 'DEMO-' . Str::random(10);
            $registration->status = 'confirmed';
            $registration->save();
            
            // Create ticket
            $ticketType = TicketType::findOrFail($request->ticket_type_id);
            
            $ticket = new Ticket([
                'registration_id' => $registration->id,
                'ticket_type_id' => $ticketType->id,
                'ticket_number' => Str::uuid(),
                'qr_code' => $this->qrCodeService->generateTicketQR($registration->id),
                'price' => $ticketType->price,
                'status' => 'valid',
            ]);
            
            $ticket->save();
            
            // Send confirmation and ticket
            $this->notificationService->sendRegistrationConfirmation($registration);
            
            DB::commit();
            
            return redirect()->route('registration.tickets.show', ['registration' => $registration, 'ticket' => $ticket])
                ->with('success', 'Payment confirmed successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Payment processing failed: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified registration
     */
    public function show(Registration $registration)
    {
        // Check if registration belongs to current user or is an admin
        if (!Auth::check() || (!Auth::user()->can('manage', Registration::class) && $registration->user_id != Auth::id())) {
            abort(403);
        }
        
        $registration->load(['event', 'tickets.ticketType']);
        
        return view('registration.show', compact('registration'));
    }

    /**
     * Show the form for editing the specified registration
     */
    public function edit(Registration $registration)
    {
        $this->authorize('update', $registration);
        
        $registration->load('event');
        
        // Get custom fields
        $customFields = RegistrationField::where('event_id', $registration->event_id)->orderBy('order')->get();
        
        return view('registration.edit', compact('registration', 'customFields'));
    }

    /**
     * Update the specified registration
     */
    public function update(UpdateRegistrationRequest $request, Registration $registration)
    {
        $this->authorize('update', $registration);
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Update registration custom fields
            $registration->custom_fields = $request->custom_fields;
            
            // Update ticket type if provided
            if ($request->has('ticket_type_id')) {
                $ticket = $registration->tickets()->first();
                if ($ticket) {
                    $ticket->ticket_type_id = $request->ticket_type_id;
                    $ticket->save();
                }
            }
            
            $registration->save();
            
            DB::commit();
            
            return redirect()->route('registration.show', $registration)
                ->with('success', 'Registration updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating registration: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified registration
     */
    public function destroy(Registration $registration)
    {
        $this->authorize('delete', $registration);
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            $registration->delete();
            
            DB::commit();
            
            return redirect()->route('events.registrations.index', $registration->event_id)
                ->with('success', 'Registration deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting registration: ' . $e->getMessage());
        }
    }
    
    /**
     * List all registrations for admin/manager
     */
    public function adminList(Request $request)
    {
        $this->authorize('viewAny', Registration::class);
        
        // Get search query
        $search = $request->input('search');
        
        // Get event filter
        $eventId = $request->input('event');
        
        // Get ticket type filter
        $ticketTypeId = $request->input('ticket_type');
        
        // Get date range filters
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        
        // Build query
        $query = Registration::with(['user', 'event', 'tickets.ticketType']);
        
        // Apply filters
        if ($eventId) {
            $query->where('event_id', $eventId);
        }
        
        if ($ticketTypeId) {
            $query->whereHas('tickets', function($q) use ($ticketTypeId) {
                $q->where('ticket_type_id', $ticketTypeId);
            });
        }
        
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        
        // Apply search
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->whereHas('user', function($uq) use ($search) {
                    $uq->where('name', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('phone_number', 'like', "%{$search}%");
                })
                ->orWhereHas('event', function($eq) use ($search) {
                    $eq->where('name', 'like', "%{$search}%");
                });
            });
        }
        
        // Restrict to events created by the current user for event managers
        if (Auth::user()->hasRole('event_manager')) {
            $query->whereHas('event', function($q) {
                $q->where('user_id', Auth::id());
            });
        }
        
        // Get paginated results
        $registrations = $query->latest()->paginate(20);
        
        // Get events for filter dropdown
        $events = Auth::user()->hasRole('admin') 
            ? Event::all() 
            : Event::where('user_id', Auth::id())->get();
        
        // Get ticket types for filter dropdown
        $ticketTypes = TicketType::all();
        
        return view('registration.admin_list', compact(
            'registrations', 
            'events', 
            'ticketTypes', 
            'search', 
            'eventId', 
            'ticketTypeId',
            'dateFrom',
            'dateTo'
        ));
    }
    
    /**
     * Export registrations to CSV
     */
    public function exportCsv(Request $request)
    {
        $this->authorize('viewAny', Registration::class);
        
        // Get event filter
        $eventId = $request->input('event');
        
        // Build query
        $query = Registration::with(['user', 'event', 'tickets.ticketType']);
        
        // Apply filter
        if ($eventId) {
            $query->where('event_id', $eventId);
        }
        
        // Restrict to events created by the current user for event managers
        if (Auth::user()->hasRole('event_manager')) {
            $query->whereHas('event', function($q) {
                $q->where('user_id', Auth::id());
            });
        }
        
        // Get all registrations
        $registrations = $query->get();
        
        // Create CSV
        $filename = 'registrations_' . date('Y-m-d') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ];
        
        $callback = function() use ($registrations) {
            $file = fopen('php://output', 'w');
            
            // Add header row
            fputcsv($file, [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Registration Date',
                'Event',
                'Ticket Type',
                'Status',
                'Payment Status',
            ]);
            
            // Add data rows
            foreach ($registrations as $registration) {
                fputcsv($file, [
                    $registration->id,
                    $registration->user->name,
                    $registration->user->email,
                    $registration->user->phone_number,
                    $registration->created_at->format('Y-m-d H:i:s'),
                    $registration->event->name,
                    $registration->tickets->first() ? $registration->tickets->first()->ticketType->name : 'N/A',
                    $registration->status,
                    $registration->payment_status,
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Import registrations from CSV
     */
    public function importCsv(Request $request)
    {
        $this->authorize('create', Registration::class);
        
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
        ]);
        
        // Open uploaded file
        $file = fopen($request->file('csv_file')->getPathname(), 'r');
        
        // Skip header row
        fgetcsv($file);
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Process rows
            while (($row = fgetcsv($file)) !== false) {
                // Extract data
                list($name, $email, $phone, $eventName, $ticketTypeName) = $row;
                
                // Find event
                $event = Event::where('name', $eventName)->first();
                if (!$event) {
                    continue; // Skip if event not found
                }
                
                // Check if user has permission to create registrations for this event
                if (Auth::user()->hasRole('event_manager') && $event->user_id != Auth::id()) {
                    continue; // Skip if user doesn't have permission
                }
                
                // Find or create user
                $user = User::firstOrCreate(
                    ['email' => $email],
                    [
                        'name' => $name,
                        'phone_number' => $phone,
                        'password' => bcrypt(Str::random(10)),
                    ]
                );
                
                // Find ticket type
                $ticketType = TicketType::where('name', $ticketTypeName)
                    ->where('event_id', $event->id)
                    ->first();
                
                // Create registration
                $registration = Registration::create([
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                ]);
                
                // Create ticket
                if ($ticketType) {
                    Ticket::create([
                        'registration_id' => $registration->id,
                        'ticket_type_id' => $ticketType->id,
                        'ticket_number' => Str::uuid(),
                        'qr_code' => $this->qrCodeService->generateTicketQR($registration->id),
                        'price' => $ticketType->price,
                        'status' => 'valid',
                    ]);
                }
            }
            
            DB::commit();
            
            return redirect()->back()->with('success', 'Registrations imported successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error importing registrations: ' . $e->getMessage());
        } finally {
            fclose($file);
        }
    }
}