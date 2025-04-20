<?php

namespace App\Http\Livewire;

use App\Models\Checkin;
use App\Models\Registration;
use Livewire\Component;

class CheckinScanner extends Component
{
    public $scannedCode = '';
    public $manualRegistrationCode = '';
    public $searchQuery = '';
    public $searchResults = [];
    public $selectedEvent = null;
    public $recentCheckins = [];
    public $error = '';
    public $success = '';
    
    public function mount()
    {
        $this->loadRecentCheckins();
    }
    
    public function loadRecentCheckins()
    {
        $this->recentCheckins = Checkin::with(['registration.user', 'registration.event'])
            ->latest()
            ->take(5)
            ->get();
    }
    
    public function processCode()
    {
        $this->validate([
            'scannedCode' => 'required'
        ]);
        
        // This would be replaced with actual QR code processing logic
        // For now, we'll assume the code contains the registration ID
        $registrationId = $this->extractRegistrationId($this->scannedCode);
        
        if (!$registrationId) {
            $this->error = 'Invalid QR code';
            return;
        }
        
        $this->processRegistration($registrationId);
    }
    
    public function processManualCode()
    {
        $this->validate([
            'manualRegistrationCode' => 'required'
        ]);
        
        // Assume the manual code is directly the registration number or ID
        $registration = Registration::where('registration_number', $this->manualRegistrationCode)
            ->orWhere('id', $this->manualRegistrationCode)
            ->first();
            
        if (!$registration) {
            $this->error = 'Registration not found';
            return;
        }
        
        $this->processRegistration($registration->id);
    }
    
    public function search()
    {
        if (strlen($this->searchQuery) < 3) {
            $this->searchResults = [];
            return;
        }
        
        $this->searchResults = Registration::with(['user', 'event'])
            ->where(function($query) {
                $query->whereHas('user', function($q) {
                    $q->where('name', 'like', '%' . $this->searchQuery . '%')
                        ->orWhere('email', 'like', '%' . $this->searchQuery . '%');
                })
                ->orWhere('registration_number', 'like', '%' . $this->searchQuery . '%');
            })
            ->when($this->selectedEvent, function($query) {
                $query->where('event_id', $this->selectedEvent);
            })
            ->take(5)
            ->get();
    }
    
    public function selectRegistration($id)
    {
        $this->processRegistration($id);
        $this->searchResults = [];
        $this->searchQuery = '';
    }
    
    private function extractRegistrationId($code)
    {
        // Simple example - in reality you might decode a QR code format
        if (preg_match('/REGISTRATION-(\d+)/', $code, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    private function processRegistration($registrationId)
    {
        try {
            $registration = Registration::findOrFail($registrationId);
            
            // Create a new check-in record
            $checkin = new Checkin([
                'registration_id' => $registration->id,
                'user_id' => auth()->id(),
                'check_in_time' => now(),
            ]);
            
            $checkin->save();
            
            $this->success = "{$registration->user->name} checked in successfully!";
            $this->error = '';
            
            $this->loadRecentCheckins();
            
            // Reset inputs
            $this->scannedCode = '';
            $this->manualRegistrationCode = '';
            
        } catch (\Exception $e) {
            $this->error = "Error processing check-in: {$e->getMessage()}";
            $this->success = '';
        }
    }
    
    public function render()
    {
        $events = \App\Models\Event::where('end_date', '>=', now())->get();
        
        return view('livewire.checkin-scanner', [
            'events' => $events
        ]);
    }
}