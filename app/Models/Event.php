<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Event extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'venue_id',
        'category_id',
        'is_active',
        'logo',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Get the venue that hosts the event.
     */
    public function venue()
    {
        return $this->belongsTo(Venue::class);
    }

    /**
     * Get the category of the event.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who created the event.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the registrations for the event.
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * Get the recurrences for the event.
     */
    public function recurrences()
    {
        return $this->hasMany(Recurrence::class);
    }

    /**
     * Get the registration fields for the event.
     */
    public function registrationFields()
    {
        return $this->hasMany(RegistrationField::class);
    }

    /**
     * Get the ticket types for the event.
     */
    public function ticketTypes()
    {
        return $this->hasMany(TicketType::class);
    }

    /**
     * Get the full URL for the event logo.
     */
    public function getLogoUrlAttribute()
    {
        if (!$this->logo) {
            return null;
        }
        
        return Storage::url($this->logo);
    }

    /**
     * Delete the event logo.
     */
    public function deleteLogo()
    {
        if ($this->logo) {
            Storage::disk('public')->delete($this->logo);
            $this->update(['logo' => null]);
        }
    }

    /**
     * Check if the event has an upcoming session.
     */
    public function hasUpcomingSessions()
    {
        return $this->start_date->isFuture() || 
               ($this->start_date->isPast() && $this->end_date->isFuture());
    }

    /**
     * Scope a query to only include active events.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now());
    }

    /**
     * Scope a query to only include ongoing events.
     */
    public function scopeOngoing($query)
    {
        return $query->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
    }

    /**
     * Scope a query to only include past events.
     */
    public function scopePast($query)
    {
        return $query->where('end_date', '<', now());
    }
}