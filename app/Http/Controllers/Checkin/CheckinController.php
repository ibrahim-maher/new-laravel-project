<?php

namespace App\Http\Controllers\Checkin;

use App\Http\Controllers\Controller;
use App\Models\Checkin;
use App\Models\Registration;
use App\Models\Ticket;
use App\Models\User;
use App\Models\VisitorLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckinController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:admin,event_manager,usher');
    }

    /**
     * Display the check-in page
     */
    public function index()
    {
        // Get recent check-ins for today
        $recentCheckins = Checkin::with(['registration.user', 'registration.event', 'ticket.ticketType'])
            ->whereDate('check_in_time', Carbon::today())
            ->latest('check_in_time')
            ->limit(10)
            ->get();
        
        // Count total check-ins for today
        $todayCheckinCount = Checkin::whereDate('check_in_time', Carbon::today())->count();
        
        return view('checkin.checkin', compact('recentCheckins', 'todayCheckinCount'));
    }

    /**
     * Display the check-out page
     */
    public function checkout()
    {
        // Get recent check-ins for today
        $recentCheckins = Checkin::with(['registration.user', 'registration.event', 'ticket.ticketType'])
            ->whereDate('check_in_time', Carbon::today())
            ->latest('check_in_time')
            ->limit(10)
            ->get();
        
        // Count total check-ins for today
        $todayCheckinCount = Checkin::whereDate('check_in_time', Carbon::today())->count();
        
        return view('checkin.checkout', compact('recentCheckins', 'todayCheckinCount'));
    }

    /**
     * Process QR scan for check-in/check-out
     */
    public function processQrScan(Request $request)
    {
        try {
            $data = json_decode($request->getContent(), true);
            $registrationId = $data['registration_id'];

            DB::beginTransaction();
            
            $registration = Registration::findOrFail($registrationId);

            if (!$registration->event->is_active) {
                return response()->json(['status' => 'error', 'message' => 'Event is not active'], 400);
            }

            $lastLog = Checkin::where('registration_id', $registration->id)
                ->orderBy('check_in_time', 'desc')
                ->first();
            
            $action = $lastLog && !$lastLog->check_out_time ? 'checkout' : 'checkin';

            $checkin = new Checkin([
                'registration_id' => $registration->id,
                'ticket_id' => $registration->tickets->first()->id ?? null,
                'user_id' => Auth::id(),
                'check_in_time' => now(),
                'check_out_time' => $action === 'checkout' ? now() : null,
                'notes' => $action === 'checkout' ? 'Auto check-out via QR scan' : null
            ]);
            
            $checkin->save();

            if ($action === 'checkout') {
                // Calculate duration and log visit
                $duration = $lastLog->check_in_time->diffInMinutes(now());
                
                VisitorLog::create([
                    'event_id' => $registration->event_id,
                    'registration_id' => $registration->id,
                    'check_in_time' => $lastLog->check_in_time,
                    'check_out_time' => now(),
                    'duration' => $duration
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => 'success',
                'action' => $action,
                'timestamp' => $checkin->check_in_time->toIso8601String(),
                'user' => $registration->user->name
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Process manual check-in
     */
    public function manualCheckin(Request $request)
    {
        $request->validate([
            'registration_id' => 'required|exists:registrations,id',
            'action' => 'required|in:checkin,checkout'
        ]);

        try {
            DB::beginTransaction();
            
            $registration = Registration::findOrFail($request->registration_id);
            
            $checkin = new Checkin([
                'registration_id' => $registration->id,
                'ticket_id' => $registration->tickets->first()->id ?? null,
                'user_id' => Auth::id(),
                'check_in_time' => now(),
                'check_out_time' => $request->action === 'checkout' ? now() : null,
                'notes' => $request->admin_note ?? null
            ]);
            
            $checkin->save();

            if ($request->action === 'checkout') {
                // Look for previous check-in
                $lastCheckin = Checkin::where('registration_id', $registration->id)
                    ->where('check_out_time', null)
                    ->orderBy('check_in_time', 'desc')
                    ->first();
                
                if ($lastCheckin) {
                    // Calculate duration and log visit
                    $duration = $lastCheckin->check_in_time->diffInMinutes(now());
                    
                    VisitorLog::create([
                        'event_id' => $registration->event_id,
                        'registration_id' => $registration->id,
                        'check_in_time' => $lastCheckin->check_in_time,
                        'check_out_time' => now(),
                        'duration' => $duration
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'status' => 'success', 
                'message' => 'Successfully processed ' . $request->action
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Process check-out for a specific check-in
     */
    public function processCheckout(Checkin $checkin)
    {
        try {
            DB::beginTransaction();
            
            // Set check-out time
            $checkin->check_out_time = now();
            $checkin->save();
            
            // Calculate duration and log visit
            $duration = $checkin->check_in_time->diffInMinutes($checkin->check_out_time);
            
            VisitorLog::create([
                'event_id' => $checkin->registration->event_id,
                'registration_id' => $checkin->registration_id,
                'check_in_time' => $checkin->check_in_time,
                'check_out_time' => $checkin->check_out_time,
                'duration' => $duration
            ]);
            
            DB::commit();
            
            return redirect()->route('checkin.index')
                ->with('success', 'Check-out processed successfully for ' . $checkin->registration->user->name);
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error processing checkout: ' . $e->getMessage());
        }
    }

    /**
     * Display screen for badge generation
     */
    public function badgeScreen()
    {
        return view('checkin.registration_badge');
    }
}