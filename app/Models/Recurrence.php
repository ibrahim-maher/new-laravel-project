<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Recurrence extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'event_id',
        'recurrence_type',
        'interval',
        'end_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'interval' => 'integer',
        'end_date' => 'datetime',
    ];

    /**
     * Available recurrence types.
     */
    const RECURRENCE_TYPES = [
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',
    ];

    /**
     * Get the event that the recurrence belongs to.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get all future occurrences of this recurring event.
     *
     * @return array
     */
    public function getFutureOccurrences()
    {
        $occurrences = [];
        $event = $this->event;
        $start = Carbon::parse($event->start_date);
        $end = Carbon::parse($event->end_date);
        $duration = $end->diffInSeconds($start);
        $current = Carbon::parse($start);
        
        while ($current <= $this->end_date) {
            if ($current >= now()) {
                $occurrenceEnd = (clone $current)->addSeconds($duration);
                $occurrences[] = [
                    'start' => $current->toDateTimeString(),
                    'end' => $occurrenceEnd->toDateTimeString(),
                ];
            }
            
            // Add the interval based on recurrence type
            switch ($this->recurrence_type) {
                case 'daily':
                    $current->addDays($this->interval);
                    break;
                case 'weekly':
                    $current->addWeeks($this->interval);
                    break;
                case 'monthly':
                    $current->addMonths($this->interval);
                    break;
                default:
                    break;
            }
        }
        
        return $occurrences;
    }

    /**
     * Generate the next occurrence of this recurring event.
     *
     * @return Event|null
     */
    public function generateNextOccurrence()
    {
        $occurrences = $this->getFutureOccurrences();
        
        if (empty($occurrences)) {
            return null;
        }
        
        $nextOccurrence = $occurrences[0];
        $event = $this->event;
        
        // Create a new event based on the recurrence
        $newEvent = $event->replicate();
        $newEvent->start_date = $nextOccurrence['start'];
        $newEvent->end_date = $nextOccurrence['end'];
        $newEvent->is_active = false;
        $newEvent->save();
        
        return $newEvent;
    }

    /**
     * Get the recurrence rule as text
     */
    public function getRecurrenceRuleAttribute()
    {
        $type = self::RECURRENCE_TYPES[$this->recurrence_type] ?? $this->recurrence_type;
        
        return "Every {$this->interval} {$type} until {$this->end_date->format('Y-m-d')}";
    }
}