<?php

namespace App\Http\Livewire;

use App\Models\Event;
use Livewire\Component;
use Carbon\Carbon;

class EventCalendar extends Component
{
    public $events = [];
    public $month;
    public $year;
    public $days = [];
    public $monthName;
    
    public function mount()
    {
        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
        $this->loadEvents();
    }
    
    public function loadEvents()
    {
        $startOfMonth = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth();
        
        $this->events = Event::whereBetween('start_date', [$startOfMonth, $endOfMonth])
            ->get()
            ->map(function($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->name,
                    'start' => $event->start_date->format('Y-m-d'),
                    'end' => $event->end_date->format('Y-m-d'),
                    'url' => route('events.show', $event),
                    'is_active' => $event->is_active,
                ];
            })
            ->groupBy(function($event) {
                return $event['start'];
            })
            ->toArray();
            
        $this->buildCalendar();
    }
    
    public function buildCalendar()
    {
        $this->days = [];
        $this->monthName = Carbon::createFromDate($this->year, $this->month, 1)->format('F Y');
        
        $startOfMonth = Carbon::createFromDate($this->year, $this->month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($this->year, $this->month, 1)->endOfMonth();
        
        // Get the first day of the month and how many days to show from previous month
        $firstDayOfMonth = $startOfMonth->dayOfWeek;
        $daysInPrevMonth = $firstDayOfMonth;
        
        // Get the last day of the month and how many days to show from next month
        $lastDayOfMonth = $endOfMonth->dayOfWeek;
        $daysInNextMonth = 6 - $lastDayOfMonth;
        
        // Previous month days
        $prevMonth = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $prevMonthDays = $prevMonth->daysInMonth;
        
        for ($i = $prevMonthDays - $daysInPrevMonth + 1; $i <= $prevMonthDays; $i++) {
            $date = Carbon::createFromDate($prevMonth->year, $prevMonth->month, $i)->format('Y-m-d');
            $this->days[] = [
                'date' => $date,
                'day' => $i,
                'isCurrentMonth' => false,
                'events' => $this->events[$date] ?? []
            ];
        }
        
        // Current month days
        $daysInMonth = $endOfMonth->day;
        for ($i = 1; $i <= $daysInMonth; $i++) {
            $date = Carbon::createFromDate($this->year, $this->month, $i)->format('Y-m-d');
            $this->days[] = [
                'date' => $date,
                'day' => $i,
                'isCurrentMonth' => true,
                'isToday' => Carbon::createFromDate($this->year, $this->month, $i)->isToday(),
                'events' => $this->events[$date] ?? []
            ];
        }
        
        // Next month days
        $nextMonth = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        
        for ($i = 1; $i <= $daysInNextMonth; $i++) {
            $date = Carbon::createFromDate($nextMonth->year, $nextMonth->month, $i)->format('Y-m-d');
            $this->days[] = [
                'date' => $date,
                'day' => $i,
                'isCurrentMonth' => false,
                'events' => $this->events[$date] ?? []
            ];
        }
    }
    
    public function previousMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->month = $date->month;
        $this->year = $date->year;
        $this->loadEvents();
    }
    
    public function nextMonth()
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->month = $date->month;
        $this->year = $date->year;
        $this->loadEvents();
    }
    
    public function render()
    {
        return view('livewire.event-calendar');
    }
}