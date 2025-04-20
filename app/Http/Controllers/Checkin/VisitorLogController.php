namespace App\Http\Controllers\Checkin;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\Event;
use App\Models\VisitorLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LogController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,event_manager,usher');
    }

    /**
     * Display visitor logs with filtering
     */
    public function visitorLogs(Request $request)
    {
        // Start query
        $logs = VisitorLog::with(['registration.user', 'registration.event', 'createdBy']);

        // Fetch all events for the dropdown
        $events = Event::all();

        // Fetch all users for the "Created By" dropdown
        $createdByUsers = User::whereHas('createdLogs')->distinct()->get();

        // Apply filters
        if ($request->filled('event')) {
            $logs->where('event_id', $request->event);
        }
        
        if ($request->filled('date_from')) {
            $logs->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $logs->whereDate('created_at', '<=', $request->date_to);
        }
        
        if ($request->filled('action')) {
            $logs->where('action', $request->action);
        }
        
        if ($request->filled('created_by')) {
            $logs->where('created_by', $request->created_by);
        }

        // Get paginated results
        $logs = $logs->paginate(50);

        // Statistics
        $stats = [
            'total_checkins' => VisitorLog::where('action', 'checkin')->count(),
            'total_checkouts' => VisitorLog::where('action', 'checkout')->count(),
        ];

        // Hourly distribution for chart
        $hourlyDistribution = VisitorLog::select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hourlyLabels = $hourlyDistribution->pluck('hour')->map(function($hour) {
            return sprintf('%02d:00', $hour);
        });
        
        $hourlyData = $hourlyDistribution->pluck('count');

        return view('checkin.visitor_log', compact(
            'logs', 
            'stats', 
            'events', 
            'createdByUsers',
            'hourlyLabels',
            'hourlyData'
        ));
    }
}