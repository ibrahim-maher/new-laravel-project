<?php

namespace App\Services;

use App\Models\Event;
use Carbon\Carbon;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class CalendarSyncService
{
    protected $client;
    
    /**
     * Initialize the Google Calendar client
     */
    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setApplicationName(config('app.name'));
        $this->client->setScopes([Google_Service_Calendar::CALENDAR]);
        $this->client->setAuthConfig(storage_path('app/google-calendar/credentials.json'));
        $this->client->setAccessType('offline');
    }
    
    /**
     * Sync events to Google Calendar
     *
     * @param Event $event Laravel event model
     * @return string|null Google Calendar event ID or null on failure
     */
    public function syncToGoogle(Event $event)
    {
        try {
            $calendar = new Google_Service_Calendar($this->client);
            
            // Create Google Calendar event
            $googleEvent = new Google_Service_Calendar_Event([
                'summary' => $event->name,
                'description' => $event->description,
                'start' => [
                    'dateTime' => $event->start_date->format('c')
                ],
                'end' => [
                    'dateTime' => $event->end_date 
                        ? $event->end_date->format('c') 
                        : $event->start_date->addHours(1)->format('c')
                ],
                'location' => $event->venue ? $event->venue->address : '',
                'colorId' => $event->category ? $this->mapCategoryToColor($event->category->color_code) : 1
            ]);
            
            // Create event in Google Calendar
            $calendarId = config('services.google.calendar_id');
            $createdEvent = $calendar->events->insert($calendarId, $googleEvent);
            
            return $createdEvent->getId();
        } catch (\Exception $e) {
            return null;
        }
    }
    
    /**
     * Generate iCalendar file for an event
     *
     * @param Event $event
     * @return string iCal content
     */
    public function generateICalendar(Event $event)
    {
        $ical = "BEGIN:VCALENDAR\r\n";
        $ical .= "VERSION:2.0\r\n";
        $ical .= "PRODID:-//" . config('app.name') . "//EN\r\n";
        $ical .= "CALSCALE:GREGORIAN\r\n";
        $ical .= "METHOD:PUBLISH\r\n";
        
        // Event data
        $ical .= "BEGIN:VEVENT\r\n";
        $ical .= "UID:" . md5($event->id . $event->name) . "@" . config('app.url') . "\r\n";
        $ical .= "DTSTAMP:" . Carbon::now()->format('Ymd\THis\Z') . "\r\n";
        $ical .= "DTSTART:" . $event->start_date->format('Ymd\THis\Z') . "\r\n";
        
        if ($event->end_date) {
            $ical .= "DTEND:" . $event->end_date->format('Ymd\THis\Z') . "\r\n";
        } else {
            $ical .= "DTEND:" . $event->start_date->addHours(1)->format('Ymd\THis\Z') . "\r\n";
        }
        
        $ical .= "SUMMARY:" . $this->escapeIcalText($event->name) . "\r\n";
        
        if ($event->description) {
            $ical .= "DESCRIPTION:" . $this->escapeIcalText($event->description) . "\r\n";
        }
        
        if ($event->venue) {
            $ical .= "LOCATION:" . $this->escapeIcalText($event->venue->name . ', ' . $event->venue->address) . "\r\n";
        }
        
        $ical .= "END:VEVENT\r\n";
        $ical .= "END:VCALENDAR";
        
        return $ical;
    }
    
    /**
     * Map color code to Google Calendar color ID
     *
     * @param string $colorCode
     * @return int Google Calendar color ID
     */
    protected function mapCategoryToColor($colorCode)
    {
        // Google Calendar color IDs (approximate mapping)
        $colorMap = [
            '#a4bdfc' => 1,  // Lavender
            '#7ae7bf' => 2,  // Sage
            '#dbadff' => 3,  // Grape
            '#ff887c' => 4,  // Flamingo
            '#fbd75b' => 5,  // Banana
            '#ffb878' => 6,  // Tangerine
            '#46d6db' => 7,  // Peacock
            '#e1e1e1' => 8,  // Graphite
            '#5484ed' => 9,  // Blueberry
            '#51b749' => 10, // Basil
            '#dc2127' => 11  // Tomato
        ];
        
        // Default to Blueberry (9) if no match
        if (!$colorCode) {
            return 9;
        }
        
        // Find closest color
        $closest = null;
        $closestDistance = PHP_INT_MAX;
        
        list($r, $g, $b) = sscanf($colorCode, "#%02x%02x%02x");
        
        foreach ($colorMap as $hex => $id) {
            list($cr, $cg, $cb) = sscanf($hex, "#%02x%02x%02x");
            
            // Simple Euclidean distance in RGB space
            $distance = sqrt(
                pow($r - $cr, 2) +
                pow($g - $cg, 2) +
                pow($b - $cb, 2)
            );
            
            if ($distance < $closestDistance) {
                $closestDistance = $distance;
                $closest = $id;
            }
        }
        
        return $closest;
    }
    
    /**
     * Escape text for iCalendar format
     *
     * @param string $text
     * @return string
     */
    protected function escapeIcalText($text)
    {
        $text = str_replace("\\", "\\\\", $text);
        $text = str_replace("\n", "\\n", $text);
        $text = str_replace("\r", "", $text);
        $text = str_replace(",", "\\,", $text);
        $text = str_replace(";", "\\;", $text);
        
        return $text;
    }
}