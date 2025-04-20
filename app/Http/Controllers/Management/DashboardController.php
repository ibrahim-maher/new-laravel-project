namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use App\Models\Checkin;
use App\Models\User;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Route to appropriate dashboard based on user role
     */
    public function index()
    {
        if (Auth::user()->hasRole('admin')) {
            return $this->admin();
        } elseif (Auth::user()->hasRole('event_manager')) {
            return $this->eventManager();
        } elseif (Auth::user()->hasRole('usher')) {
            return $this->usher();
        } else {
            return $this->visitor();
        }
    }

    /**
     * Admin dashboard
     */
    public function admin()
    {
        // Count total events
        $totalEvents = Event::count();
        $activeEvents = Event::where('status', 'upcoming')->orWhere('status', 'ongoing')->count();
        
        // Count registrations
        $totalRegistrations = Registration::count();
        $todayRegistrations = Registration::whereDate('created_at', Carbon::today())->count();
        
        // Count check-ins
        $totalCheckins = Checkin::count();
        $todayCheckins = Checkin::whereDate('check_in_time', Carbon::today())->count();
        
        // Calculate revenue
        $totalRevenue = DB::table('tickets')
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->sum('tickets.price');
        
        $paidTickets = Ticket::where('price', '>', 0)->count();
        
        // Get upcoming events
        $upcomingEvents = Event::with(['venue', 'category'])
            ->withCount('registrations')
            ->where('start_date', '>', Carbon::now())
            ->where('status', 'upcoming')
            ->orderBy('start_date')
            ->limit(5)
            ->get();
        
        // Get recent registrations
        $recentRegistrations = Registration::with('event')
            ->latest()
            ->limit(10)
            ->get();
        
        // Get top events by registration count
        $topEvents = Event::withCount('registrations')
            ->orderBy('registrations_count', 'desc')
            ->limit(5)
            ->get();
        
        // Mock data for system stats
        $activeUsers = rand(5, 20);
        $diskUsage = rand(30, 80);
        $lastBackup = Carbon::now()->subHours(rand(1, 24));
        
        return view('management.admin-dashboard', compact(
            'totalEvents',
            'activeEvents',
            'totalRegistrations',
            'todayRegistrations',
            'totalCheckins',
            'todayCheckins',
            'totalRevenue',
            'paidTickets',
            'upcomingEvents',
            'recentRegistrations',
            'topEvents',
            'activeUsers',
            'diskUsage',
            'lastBackup'
        ));
    }

    /**
     * Event Manager dashboard
     */
    public function eventManager()
    {
        // Get events managed by current user
        $managedEvents = Event::where('user_id', Auth::id())
            ->withCount('registrations')
            ->get();
        
        // Count total managed events
        $totalManagedEvents = $managedEvents->count();
        
        // Calculate total registrations
        $totalRegistrations = $managedEvents->sum('registrations_count');
        
        // Get upcoming events
        $upcomingEvents = Event::with(['venue', 'category'])
            ->where('user_id', Auth::id())
            ->where('start_date', '>', Carbon::now())
            ->where('status', 'upcoming')
            ->orderBy('start_date')
            ->get();
        
        // Get recent registrations for managed events
        $recentRegistrations = Registration::whereIn('event_id', $managedEvents->pluck('id'))
            ->with('event')
            ->latest()
            ->limit(10)
            ->get();
        
        return view('management.event-manager-dashboard', compact(
            'managedEvents',
            'totalManagedEvents',
            'totalRegistrations',
            'upcomingEvents',
            'recentRegistrations'
        ));
    }

    /**
     * Usher dashboard
     */
    public function usher()
    {
        // Get today's check-ins
        $todayCheckins = Checkin::with(['registration.event', 'registration.user', 'user'])
            ->whereDate('check_in_time', Carbon::today())
            ->latest('check_in_time')
            ->paginate(15);
        
        // Count total check-ins for today
        $todayCheckinCount = Checkin::whereDate('check_in_time', Carbon::today())->count();
        
        // Count check-ins by current user
        $userCheckinCount = Checkin::where('user_id', Auth::id())->count();
        
        // Get active events for today
        $todayEvents = Event::where(function($query) {
            $now = Carbon::now();
            $query->whereDate('start_date', '<=', $now)
                  ->whereDate('end_date', '>=', $now);
            })
            ->orWhere(function($query) {
                $now = Carbon::now();
                $query->whereDate('start_date', $now);
            })
            ->get();
        
        return view('management.usher-dashboard', compact(
            'todayCheckins',
            'todayCheckinCount',
            'userCheckinCount',
            'todayEvents'
        ));
    }

    /**
     * Visitor dashboard
     */
    public function visitor()
    {
        // Get registrations for current user
        $userRegistrations = Registration::with(['event', 'tickets'])
            ->where('user_id', Auth::id())
            ->latest()
            ->get();
        
        // Get upcoming events the user is registered for
        $upcomingEvents = Event::whereIn('id', $userRegistrations->pluck('event_id'))
            ->where('start_date', '>', Carbon::now())
            ->get();
        
        // Get past events the user has attended
        $pastEvents = Event::whereIn('id', $userRegistrations->pluck('event_id'))
            ->where('start_date', '<', Carbon::now())
            ->get();
        
        return view('management.visitor-dashboard', compact(
            'userRegistrations',
            'upcomingEvents',
            'pastEvents'
        ));
    }
}