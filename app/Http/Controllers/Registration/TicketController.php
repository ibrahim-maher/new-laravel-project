namespace App\Http\Controllers\Registration;

use App\Http\Controllers\Controller;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\Event;
use App\Models\TicketType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\QrCodeService;

class TicketController extends Controller
{
    protected $qrCodeService;

    public function __construct(QrCodeService $qrCodeService)
    {
        $this->middleware('auth')->except(['show', 'download']);
        $this->qrCodeService = $qrCodeService;
    }

    /**
     * Display a listing of tickets for an event or for an admin
     */
    public function index(Request $request, Event $event = null)
    {
        // For admin listing all tickets
        if (!$event) {
            $this->authorize('viewAny', Ticket::class);
            
            $search = $request->input('search');
            
            $query = Ticket::with(['registration.user', 'registration.event', 'ticketType']);
            
            // Apply search filter
            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('ticket_number', 'like', "%{$search}%")
                        ->orWhereHas('registration.user', function($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        })
                        ->orWhereHas('registration.event', function($eq) use ($search) {
                            $eq->where('name', 'like', "%{$search}%");
                        });
                });
            }
            
            // Restrict to events created by the current user for event managers
            if (Auth::user()->hasRole('event_manager')) {
                $query->whereHas('registration.event', function($q) {
                    $q->where('user_id', Auth::id());
                });
            }
            
            $tickets = $query->latest()->paginate(20);
            
            return view('registration.tickets.admin_list', compact('tickets', 'search'));
        }
        
        // For listing tickets of a specific event
        $this->authorize('view', $event);
        
        $tickets = Ticket::whereHas('registration', function($q) use ($event) {
                $q->where('event_id', $event->id);
            })
            ->with(['registration.user', 'ticketType'])
            ->latest()
            ->paginate(20);
        
        return view('registration.tickets.index', compact('event', 'tickets'));
    }

    /**
     * Show the form for creating a new ticket
     */
    public function create(Registration $registration = null)
    {
        if ($registration) {
            $this->authorize('update', $registration);
            $events = [$registration->event];
            $selectedEvent = $registration->event;
        } else {
            $this->authorize('create', Ticket::class);
            
            // For admins or event managers creating new tickets
            if (Auth::user()->hasRole('admin')) {
                $events = Event::all();
            } else {
                $events = Event::where('user_id', Auth::id())->get();
            }
            
            $selectedEvent = null;
        }
        
        return view('registration.tickets.create', compact('events', 'selectedEvent', 'registration'));
    }

    /**
     * Store a newly created ticket
     */
    public function store(Request $request)
    {
        // If registration is provided, we're adding a ticket to an existing registration
        if ($request->has('registration_id')) {
            $registration = Registration::findOrFail($request->registration_id);
            $this->authorize('update', $registration);
        } else {
            $this->authorize('create', Ticket::class);
            
            // Validate event and find or create registration
            $request->validate([
                'event_id' => 'required|exists:events,id',
                'user_id' => 'required|exists:users,id',
                'ticket_type_id' => 'required|exists:ticket_types,id',
            ]);
            
            // Check if event is valid for the user
            $event = Event::findOrFail($request->event_id);
            if (!Auth::user()->hasRole('admin') && $event->user_id != Auth::id()) {
                return redirect()->back()->with('error', 'You do not have permission to create tickets for this event.');
            }
            
            // Find or create registration
            $registration = Registration::firstOrCreate(
                [
                    'event_id' => $request->event_id,
                    'user_id' => $request->user_id,
                ],
                [
                    'status' => 'confirmed',
                    'payment_status' => 'paid',
                ]
            );
        }
        
        // Validate ticket type
        $request->validate([
            'ticket_type_id' => [
                'required',
                'exists:ticket_types,id',
                function ($attribute, $value, $fail) use ($registration) {
                    $ticketType = TicketType::find($value);
                    if ($ticketType && $ticketType->event_id != $registration->event_id) {
                        $fail('The selected ticket type does not belong to the event.');
                    }
                },
            ],
        ]);
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Create ticket
            $ticket = new Ticket([
                'registration_id' => $registration->id,
                'ticket_type_id' => $request->ticket_type_id,
                'ticket_number' => Str::uuid(),
                'qr_code' => $this->qrCodeService->generateTicketQR($registration->id),
                'price' => TicketType::find($request->ticket_type_id)->price ?? 0,
                'status' => 'valid',
            ]);
            
            $ticket->save();
            
            DB::commit();
            
            return redirect()->route('registration.tickets.show', $ticket)
                ->with('success', 'Ticket created successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error creating ticket: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified ticket
     */
    public function show(Ticket $ticket)
    {
        // Check if ticket belongs to current user or is viewable by admin/manager
        if (Auth::check() && (Auth::user()->can('manage', Ticket::class) || $ticket->registration->user_id == Auth::id())) {
            $ticket->load(['registration.user', 'registration.event', 'ticketType']);
            return view('registration.tickets.show', compact('ticket'));
        }
        
        // For public ticket view with token
        $token = request('token');
        if ($token && $ticket->registration->verification_token === $token) {
            $ticket->load(['registration.user', 'registration.event', 'ticketType']);
            return view('registration.tickets.public_show', compact('ticket', 'token'));
        }
        
        abort(403);
    }

    /**
     * Download the QR code for a ticket
     */
    public function download(Ticket $ticket)
    {
        // Check if ticket belongs to current user or is viewable by admin/manager
        if (Auth::check() && (Auth::user()->can('manage', Ticket::class) || $ticket->registration->user_id == Auth::id())) {
            // Allow download
        } else {
            // For public access with token
            $token = request('token');
            if (!$token || $ticket->registration->verification_token !== $token) {
                abort(403);
            }
        }
        
        // Generate QR code image
        $qrCode = $this->qrCodeService->generateTicketQR($ticket->registration_id, true);
        
        // Return the image
        return response($qrCode)
            ->header('Content-Type', 'image/png')
            ->header('Content-Disposition', 'attachment; filename="ticket-' . $ticket->id . '.png"');
    }

    /**
     * Show the form for editing the specified ticket
     */
    public function edit(Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        
        $ticket->load(['registration.event', 'ticketType']);
        
        // Get ticket types for the event
        $ticketTypes = TicketType::where('event_id', $ticket->registration->event_id)
            ->where('is_active', true)
            ->get();
        
        return view('registration.tickets.edit', compact('ticket', 'ticketTypes'));
    }

    /**
     * Update the specified ticket
     */
    public function update(Request $request, Ticket $ticket)
    {
        $this->authorize('update', $ticket);
        
        // Validate ticket type
        $request->validate([
            'ticket_type_id' => [
                'required',
                'exists:ticket_types,id',
                function ($attribute, $value, $fail) use ($ticket) {
                    $ticketType = TicketType::find($value);
                    if ($ticketType && $ticketType->event_id != $ticket->registration->event_id) {
                        $fail('The selected ticket type does not belong to the event.');
                    }
                },
            ],
            'status' => 'required|in:valid,used,cancelled',
        ]);
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Update ticket
            $ticket->ticket_type_id = $request->ticket_type_id;
            $ticket->status = $request->status;
            
            // Update price if ticket type changed
            $newTicketType = TicketType::find($request->ticket_type_id);
            if ($newTicketType && $ticket->ticket_type_id != $request->ticket_type_id) {
                $ticket->price = $newTicketType->price;
            }
            
            $ticket->save();
            
            DB::commit();
            
            return redirect()->route('registration.tickets.show', $ticket)
                ->with('success', 'Ticket updated successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error updating ticket: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified ticket
     */
    public function destroy(Ticket $ticket)
    {
        $this->authorize('delete', $ticket);
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            $registrationId = $ticket->registration_id;
            
            // Delete the ticket
            $ticket->delete();
            
            DB::commit();
            
            return redirect()->route('registration.show', $registrationId)
                ->with('success', 'Ticket deleted successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error deleting ticket: ' . $e->getMessage());
        }
    }
}