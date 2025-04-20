<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * Get the events for the category.
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Get the count of events in this category.
     */
    public function getEventCountAttribute()
    {
        return $this->events()->count();
    }

    /**
     * Get upcoming events in this category.
     */
    public function upcomingEvents()
    {
        return $this->events()->upcoming()->get();
    }
}