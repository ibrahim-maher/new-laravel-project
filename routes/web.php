<?php

use Illuminate\Support\Facades\Route;
use App\Http\Livewire\EventsTable;
use App\Http\Livewire\EventCalendar;
use App\Http\Livewire\CheckinScanner;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Events
    Route::get('/events', function () {
        return view('events.index');
    })->name('events.index');
    
    Route::get('/events/calendar', function () {
        return view('events.calendar');
    })->name('events.calendar');
    
    Route::get('/events/create', [App\Http\Controllers\Events\EventController::class, 'create'])->name('events.create');
    Route::post('/events', [App\Http\Controllers\Events\EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}', [App\Http\Controllers\Events\EventController::class, 'show'])->name('events.show');
    Route::get('/events/{event}/edit', [App\Http\Controllers\Events\EventController::class, 'edit'])->name('events.edit');
    Route::put('/events/{event}', [App\Http\Controllers\Events\EventController::class, 'update'])->name('events.update');
    Route::delete('/events/{event}', [App\Http\Controllers\Events\EventController::class, 'destroy'])->name('events.destroy');
    
    // Registrations
    Route::get('/registrations', [App\Http\Controllers\Registration\RegistrationController::class, 'adminList'])->name('registrations.index');
    
    // Check-in
    Route::get('/checkin', function () {
        return view('checkin.index');
    })->name('checkin.index');
    
    Route::get('/checkin/logs', [App\Http\Controllers\Checkin\VisitorLogController::class, 'visitorLogs'])->name('checkin.logs');
});

require __DIR__.'/auth.php';
