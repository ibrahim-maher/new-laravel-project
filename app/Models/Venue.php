<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Venue extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'capacity',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'capacity' => 'integer',
    ];

    /**
     * Get the events for the venue.
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Check if the venue is at capacity for a specific event.
     */
    public function isAtCapacity(Event $event)
    {
        $registrationsCount = $event->registrations()->count();
        return $registrationsCount >= $this->capacity;
    }

    /**
     * Get available capacity for a specific event.
     */
    public function availableCapacity(Event $event)
    {
        $registrationsCount = $event->registrations()->count();
        return max(0, $this->capacity - $registrationsCount);
    }

    /**
     * Get upcoming events at this venue.
     */
    public function upcomingEvents()
    {
        return $this->events()->upcoming()->get();
    }
}