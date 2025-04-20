namespace App\Http\Controllers\Management;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Venue;
use App\Models\Category;
use App\Models\Registration;
use App\Models\Checkin;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,event_manager');
    }

    /**
     * Display the reports page with filters
     */
    public function index(Request $request)
    {
        $events = Event::all();
        $venues = Venue::all();
        $categories = Category::all();
        
        // Date range
        $startDate = $request->input('start_date', Carbon::now()->subMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        
        // Get event filter
        $eventId = $request->input('event_id');
        
        // Default to a month of data
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);
        
        // Attendance data
        $attendanceData = $this->getAttendanceData($startDateObj, $endDateObj, $eventId);
        
        // Registration data
        $registrationData = $this->getRegistrationData($startDateObj, $endDateObj, $eventId);
        
        // Revenue data
        $revenueData = $this->getRevenueData($startDateObj, $endDateObj, $eventId);
        
        // Demographic data
        $demographicData = $this->getDemographicData($eventId);
        
        return view('management.reports', compact(
            'events',
            'venues',
            'categories',
            'startDate',
            'endDate',
            'eventId',
            'attendanceData',
            'registrationData',
            'revenueData',
            'demographicData'
        ));
    }

    /**
     * Display charts and visualizations
     */
    public function charts(Request $request)
    {
        $events = Event::all();
        
        // Date range
        $startDate = $request->input('start_date', Carbon::now()->subMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
        
        // Get event filter
        $eventId = $request->input('event_id');
        
        // Default to a month of data
        $startDateObj = Carbon::parse($startDate);
        $endDateObj = Carbon::parse($endDate);
        
        // Chart data for registrations over time
        $registrationChartData = $this->getRegistrationChartData($startDateObj, $endDateObj, $eventId);
        
        // Chart data for attendance by hour of day
        $attendanceByHourData = $this->getAttendanceByHourData($startDateObj, $endDateObj, $eventId);
        
        // Chart data for attendance by day of week
        $attendanceByDayData = $this->getAttendanceByDayData($startDateObj, $endDateObj, $eventId);
        
        // Chart data for revenue by event category
        $revenueByCategoryData = $this->getRevenueByCategoryData($startDateObj, $endDateObj);
        
        // Chart data for registrations by ticket type
        $registrationsByTicketTypeData = $this->getRegistrationsByTicketTypeData($eventId);
        
        return view('management.charts', compact(
            'events',
            'startDate',
            'endDate',
            'eventId',
            'registrationChartData',
            'attendanceByHourData',
            'attendanceByDayData',
            'revenueByCategoryData',
            'registrationsByTicketTypeData'
        ));
    }

    /**
     * Get attendance data for the specified date range and event
     */
    private function getAttendanceData($startDate, $endDate, $eventId = null)
    {
        $query = Checkin::whereBetween('check_in_time', [$startDate, $endDate]);
        
        if ($eventId) {
            $query->whereHas('registration', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            });
        }
        
        $totalCheckins = $query->count();
        $uniqueAttendees = $query->distinct('registration_id')->count('registration_id');
        
        $checkoutCount = Checkin::whereBetween('check_in_time', [$startDate, $endDate])
            ->whereNotNull('check_out_time');
            
        if ($eventId) {
            $checkoutCount->whereHas('registration', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            });
        }
        
        $checkoutCount = $checkoutCount->count();
        
        // Average time spent
        $averageDuration = DB::table('visitor_logs')
            ->whereBetween('check_in_time', [$startDate, $endDate]);
            
        if ($eventId) {
            $averageDuration->where('event_id', $eventId);
        }
        
        $averageDuration = $averageDuration->avg('duration');
        
        // Daily breakdown
        $dailyAttendance = Checkin::select(
                DB::raw('DATE(check_in_time) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('check_in_time', [$startDate, $endDate]);
            
        if ($eventId) {
            $dailyAttendance->whereHas('registration', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            });
        }
        
        $dailyAttendance = $dailyAttendance
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return $item->count;
            });
        
        return [
            'total_checkins' => $totalCheckins,
            'unique_attendees' => $uniqueAttendees,
            'checkout_count' => $checkoutCount,
            'average_duration' => $averageDuration ? round($averageDuration) : 0,
            'daily_attendance' => $dailyAttendance,
        ];
    }

    /**
     * Get registration data for the specified date range and event
     */
    private function getRegistrationData($startDate, $endDate, $eventId = null)
    {
        $query = Registration::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($eventId) {
            $query->where('event_id', $eventId);
        }
        
        $totalRegistrations = $query->count();
        
        // Daily breakdown
        $dailyRegistrations = Registration::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        if ($eventId) {
            $dailyRegistrations->where('event_id', $eventId);
        }
        
        $dailyRegistrations = $dailyRegistrations
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return $item->count;
            });
        
        // Conversion rate (registrations to check-ins)
        $checkinCount = Checkin::whereHas('registration', function($q) use ($startDate, $endDate) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->distinct('registration_id')
            ->count('registration_id');
            
        if ($eventId) {
            $checkinCount = Checkin::whereHas('registration', function($q) use ($startDate, $endDate, $eventId) {
                $q->whereBetween('created_at', [$startDate, $endDate])
                  ->where('event_id', $eventId);
            })
            ->distinct('registration_id')
            ->count('registration_id');
        }
        
        $conversionRate = $totalRegistrations > 0 
            ? round(($checkinCount / $totalRegistrations) * 100, 2) 
            : 0;
        
        return [
            'total_registrations' => $totalRegistrations,
            'daily_registrations' => $dailyRegistrations,
            'checkin_count' => $checkinCount,
            'conversion_rate' => $conversionRate,
        ];
    }

    /**
     * Get revenue data for the specified date range and event
     */
    private function getRevenueData($startDate, $endDate, $eventId = null)
    {
        $query = Ticket::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($eventId) {
            $query->whereHas('registration', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            });
        }
        
        $totalRevenue = $query->sum('price');
        $ticketCount = $query->count();
        $averageTicketPrice = $ticketCount > 0 ? $totalRevenue / $ticketCount : 0;
        
        // Daily breakdown
        $dailyRevenue = Ticket::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(price) as sum')
            )
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        if ($eventId) {
            $dailyRevenue->whereHas('registration', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            });
        }
        
        $dailyRevenue = $dailyRevenue
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date')
            ->map(function ($item) {
                return $item->sum;
            });
        
        return [
            'total_revenue' => $totalRevenue,
            'ticket_count' => $ticketCount,
            'average_ticket_price' => $averageTicketPrice,
            'daily_revenue' => $dailyRevenue,
        ];
    }

    /**
     * Get demographic data for the specified event
     */
    private function getDemographicData($eventId = null)
    {
        // Get registration data
        $query = Registration::with('user');
        
        if ($eventId) {
            $query->where('event_id', $eventId);
        }
        
        $registrations = $query->get();
        
        // Process user demographics from registration data
        $titles = $registrations->groupBy(function ($registration) {
                return $registration->user->title;
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc();
        
        $countries = $registrations->groupBy(function ($registration) {
                return $registration->user->country;
            })
            ->map(function ($group) {
                return $group->count();
            })
            ->sortDesc();
        
        return [
            'titles' => $titles->take(10),
            'countries' => $countries->take(10),
        ];
    }

    /**
     * Get registration chart data for the specified date range and event
     */
    private function getRegistrationChartData($startDate, $endDate, $eventId = null)
    {
        $days = $startDate->diffInDays($endDate);
        
        if ($days <= 31) {
            // Daily data for short ranges
            $data = Registration::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereBetween('created_at', [$startDate, $endDate]);
                
            if ($eventId) {
                $data->where('event_id', $eventId);
            }
            
            $data = $data
                ->groupBy('date')
                ->orderBy('date')
                ->get();
            
            $labels = [];
            $counts = [];
            
            for ($date = clone $startDate; $date->lte($endDate); $date->addDay()) {
                $dateStr = $date->format('Y-m-d');
                $labels[] = $date->format('M d');
                
                $record = $data->firstWhere('date', $dateStr);
                $counts[] = $record ? $record->count : 0;
            }
        } else {
            // Weekly data for longer ranges
            $data = Registration::select(
                    DB::raw('YEARWEEK(created_at, 1) as yearweek'),
                    DB::raw('MIN(created_at) as week_start'),
                    DB::raw('COUNT(*) as count')
                )
                ->whereBetween('created_at', [$startDate, $endDate]);
                
            if ($eventId) {
                $data->where('event_id', $eventId);
            }
            
            $data = $data
                ->groupBy('yearweek')
                ->orderBy('yearweek')
                ->get();
            
            $labels = $data->map(function ($item) {
                return Carbon::parse($item->week_start)->format('M d');
            });
            
            $counts = $data->pluck('count');
        }
        
        return [
            'labels' => $labels,
            'data' => $counts,
        ];
    }

    /**
     * Get attendance by hour of day data for the specified date range and event
     */
    private function getAttendanceByHourData($startDate, $endDate, $eventId = null)
    {
        $data = Checkin::select(
                DB::raw('HOUR(check_in_time) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('check_in_time', [$startDate, $endDate]);
            
        if ($eventId) {
            $data->whereHas('registration', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            });
        }
        
        $data = $data
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();
        
        $hourLabels = [];
        $counts = [];
        
        for ($hour = 0; $hour < 24; $hour++) {
            $hourLabels[] = sprintf('%02d:00', $hour);
            
            $record = $data->firstWhere('hour', $hour);
            $counts[] = $record ? $record->count : 0;
        }
        
        return [
            'labels' => $hourLabels,
            'data' => $counts,
        ];
    }

    /**
     * Get attendance by day of week data for the specified date range and event
     */
    private function getAttendanceByDayData($startDate, $endDate, $eventId = null)
    {
        $data = Checkin::select(
                DB::raw('DAYOFWEEK(check_in_time) as day'),
                DB::raw('COUNT(*) as count')
            )
            ->whereBetween('check_in_time', [$startDate, $endDate]);
            
        if ($eventId) {
            $data->whereHas('registration', function($q) use ($eventId) {
                $q->where('event_id', $eventId);
            });
        }
        
        $data = $data
            ->groupBy('day')
            ->orderBy('day')
            ->get();
        
        $dayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        $counts = [0, 0, 0, 0, 0, 0, 0];
        
        foreach ($data as $item) {
            // DAYOFWEEK in MySQL is 1 (Sunday) to 7 (Saturday)
            $index = $item->day - 1;
            $counts[$index] = $item->count;
        }
        
        return [
            'labels' => $dayLabels,
            'data' => $counts,
        ];
    }

    /**
     * Get revenue by category data for the specified date range
     */
    private function getRevenueByCategoryData($startDate, $endDate)
    {
        $data = DB::table('tickets')
            ->join('registrations', 'tickets.registration_id', '=', 'registrations.id')
            ->join('events', 'registrations.event_id', '=', 'events.id')
            ->join('categories', 'events.category_id', '=', 'categories.id')
            ->select(
                'categories.name',
                DB::raw('SUM(tickets.price) as total')
            )
            ->whereBetween('tickets.created_at', [$startDate, $endDate])
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->get();
        
        return [
            'labels' => $data->pluck('name'),
            'data' => $data->pluck('total'),
        ];
    }

    /**
     * Get registrations by ticket type data for the specified event
     */
    private function getRegistrationsByTicketTypeData($eventId = null)
    {
        $query = DB::table('tickets')
            ->join('ticket_types', 'tickets.ticket_type_id', '=', 'ticket_types.id')
            ->join('registrations', 'tickets.registration_id', '=', 'registrations.id')
            ->select(
                'ticket_types.name',
                DB::raw('COUNT(*) as count')
            );
            
        if ($eventId) {
            $query->where('registrations.event_id', $eventId);
        }
        
        $data = $query
            ->groupBy('ticket_types.name')
            ->orderByDesc('count')
            ->get();
        
        return [
            'labels' => $data->pluck('name'),
            'data' => $data->pluck('count'),
        ];
    }
}