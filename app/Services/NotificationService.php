<?php

namespace App\Services;

use App\Models\Registration;
use Illuminate\Support\Facades\Mail;
use App\Mail\RegistrationConfirmation;
use App\Mail\TicketIssued;

class NotificationService
{
    /**
     * Send registration confirmation email
     *
     * @param Registration $registration
     * @return void
     */
    public function sendRegistrationConfirmation(Registration $registration)
    {
        // Load necessary relationships
        $registration->load(['event', 'tickets.ticketType']);
        
        // Send confirmation email
        Mail::to($registration->email)
            ->send(new RegistrationConfirmation($registration));
        
        // If WhatsApp notification is enabled and there's a phone number, send SMS
        if (config('app.whatsapp_enabled') && $registration->phone) {
            $this->sendWhatsAppNotification($registration);
        }
    }
    
    /**
     * Send ticket issued email
     *
     * @param Registration $registration
     * @return void
     */
    public function sendTicketIssued(Registration $registration)
    {
        // Load necessary relationships
        $registration->load(['event', 'tickets.ticketType']);
        
        // Send ticket email
        Mail::to($registration->email)
            ->send(new TicketIssued($registration));
    }
    
    /**
     * Send WhatsApp notification
     * Note: This is a placeholder - actual implementation would depend on
     * which WhatsApp API service you're using (Twilio, MessageBird, etc.)
     *
     * @param Registration $registration
     * @return void
     */
    protected function sendWhatsAppNotification(Registration $registration)
    {
        // This would be integrated with an actual WhatsApp API service
        // For example, using Twilio:
        
        /*
        $twilio = new \Twilio\Rest\Client(
            config('services.twilio.sid'),
            config('services.twilio.token')
        );
        
        $message = "Thank you for registering for {$registration->event->name}! ";
        $message .= "Your registration has been confirmed. ";
        $message .= "Please check your email for your ticket.";
        
        $twilio->messages->create(
            "whatsapp:{$registration->phone}",
            [
                "from" => "whatsapp:" . config('services.twilio.whatsapp_from'),
                "body" => $message
            ]
        );
        */
    }
}