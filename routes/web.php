<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Controllers
use App\Http\Controllers\HomeController;

use App\Http\Controllers\Events\EventController;
use App\Http\Controllers\Events\CategoryController;
use App\Http\Controllers\Events\VenueController;
use App\Http\Controllers\Events\RecurrenceController;
use App\Http\Controllers\Registration\RegistrationController;
use App\Http\Controllers\Registration\RegistrationFieldController;
use App\Http\Controllers\Registration\TicketController;
use App\Http\Controllers\Registration\TicketTypeController;
use App\Http\Controllers\Badges\BadgeController;
use App\Http\Controllers\Checkin\CheckinController;
use App\Http\Controllers\Checkin\VisitorLogController;
use App\Http\Controllers\Management\DashboardController;
use App\Http\Controllers\Management\ReportController;
use App\Http\Controllers\Management\ExportManagementController;

// Home and Authentication Routes
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Auth::routes();
Route::get('/home', [HomeController::class, 'index'])->name('home');

// Events Routes
Route::prefix('events')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('events.index');
    Route::get('/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/', [EventController::class, 'store'])->name('events.store');
    Route::get('/{id}', [EventController::class, 'show'])->name('events.show');
    Route::get('/{id}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::put('/{id}', [EventController::class, 'update'])->name('events.update');
    Route::delete('/{id}', [EventController::class, 'destroy'])->name('events.destroy');
    
    // Calendar View
    Route::get('/calendar/view', [EventController::class, 'calendar'])->name('events.calendar');
    
    // Export/Import
    Route::get('/export', [EventController::class, 'export'])->name('events.export');
    Route::post('/import', [EventController::class, 'import'])->name('events.import');
    
    // Categories Routes
    Route::resource('categories', CategoryController::class);
    
    // Venues Routes
    Route::resource('venues', VenueController::class);
    
    // Recurrences Routes
    Route::resource('recurrences', RecurrenceController::class);
});

// Registration Routes
Route::prefix('registrations')->group(function () {
    Route::get('/', [RegistrationController::class, 'index'])->name('registrations.index');
    Route::get('/create', [RegistrationController::class, 'create'])->name('registrations.create');
    Route::post('/', [RegistrationController::class, 'store'])->name('registrations.store');
    Route::get('/{id}', [RegistrationController::class, 'show'])->name('registrations.show');
    Route::get('/{id}/edit', [RegistrationController::class, 'edit'])->name('registrations.edit');
    Route::put('/{id}', [RegistrationController::class, 'update'])->name('registrations.update');
    Route::delete('/{id}', [RegistrationController::class, 'destroy'])->name('registrations.destroy');
    
    // Registration Fields Routes
    Route::resource('fields', RegistrationFieldController::class)->names([
        'index' => 'registrations.fields.index',
        'create' => 'registrations.fields.create',
        'store' => 'registrations.fields.store',
        'show' => 'registrations.fields.show',
        'edit' => 'registrations.fields.edit',
        'update' => 'registrations.fields.update',
        'destroy' => 'registrations.fields.destroy',
    ]);
    
    // Ticket Routes
    Route::resource('tickets', TicketController::class)->names([
        'index' => 'registrations.tickets.index',
        'create' => 'registrations.tickets.create',
        'store' => 'registrations.tickets.store',
        'show' => 'registrations.tickets.show',
        'edit' => 'registrations.tickets.edit',
        'update' => 'registrations.tickets.update',
        'destroy' => 'registrations.tickets.destroy',
    ]);
    
    // Ticket Download Route
    Route::get('/tickets/{ticket}/download', [TicketController::class, 'download'])->name('registrations.tickets.download');
    
    // Ticket Types Routes
    Route::resource('ticket-types', TicketTypeController::class)->names([
        'index' => 'registrations.ticket-types.index',
        'create' => 'registrations.ticket-types.create',
        'store' => 'registrations.ticket-types.store',
        'show' => 'registrations.ticket-types.show',
        'edit' => 'registrations.ticket-types.edit',
        'update' => 'registrations.ticket-types.update',
        'destroy' => 'registrations.ticket-types.destroy',
    ]);
});

// Badges Routes
Route::prefix('badges')->group(function () {
    Route::get('/', [BadgeController::class, 'index'])->name('badges.index');
    Route::get('/create', [BadgeController::class, 'create'])->name('badges.create');
    Route::post('/', [BadgeController::class, 'store'])->name('badges.store');
    Route::get('/{id}', [BadgeController::class, 'show'])->name('badges.show');
    Route::get('/{id}/edit', [BadgeController::class, 'edit'])->name('badges.edit');
    Route::put('/{id}', [BadgeController::class, 'update'])->name('badges.update');
    Route::delete('/{id}', [BadgeController::class, 'destroy'])->name('badges.destroy');
    
    // Badge Templates
    Route::get('/templates', [BadgeController::class, 'templates'])->name('badges.templates');
    Route::get('/templates/create', [BadgeController::class, 'createTemplate'])->name('badges.templates.create');
    Route::post('/templates', [BadgeController::class, 'storeTemplate'])->name('badges.templates.store');
    Route::get('/templates/{template}/add-content', [BadgeController::class, 'addContent'])->name('badges.templates.add-content');
    
    // Badge Preview and Print
    Route::post('/preview', [BadgeController::class, 'preview'])->name('badges.preview');
    Route::get('/print/{registration}', [BadgeController::class, 'print'])->name('badges.print');
    Route::post('/print-bulk', [BadgeController::class, 'printBulk'])->name('badges.print-bulk');
    Route::get('/registration/{registration}', [BadgeController::class, 'getRegistrationBadge'])->name('badges.registration');
});

// Check-in Routes
Route::prefix('checkin')->group(function () {
    Route::get('/', [CheckinController::class, 'index'])->name('checkin.index');
    Route::get('/create', [CheckinController::class, 'create'])->name('checkin.create');
    Route::post('/', [CheckinController::class, 'store'])->name('checkin.store');
    Route::get('/{id}', [CheckinController::class, 'show'])->name('checkin.show');
    Route::get('/{id}/edit', [CheckinController::class, 'edit'])->name('checkin.edit');
    Route::put('/{id}', [CheckinController::class, 'update'])->name('checkin.update');
    Route::delete('/{id}', [CheckinController::class, 'destroy'])->name('checkin.destroy');
    
    // Visitor Logs
    Route::resource('logs', VisitorLogController::class)->names([
        'index' => 'checkin.logs.index',
        'create' => 'checkin.logs.create',
        'store' => 'checkin.logs.store',
        'show' => 'checkin.logs.show',
        'edit' => 'checkin.logs.edit',
        'update' => 'checkin.logs.update',
        'destroy' => 'checkin.logs.destroy',
    ]);
});

// Management Routes
Route::prefix('management')->name('management.')->middleware(['auth', 'role:admin,event_manager'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    
    // Export/Import Routes
    Route::get('/export', [ExportManagementController::class, 'index'])->name('export.index');
    Route::get('/export/{type}', [ExportManagementController::class, 'export'])->name('export');
    Route::post('/import/{type}', [ExportManagementController::class, 'import'])->name('import');
});

// User Management Routes
Route::prefix('users')->name('users.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/', [App\Http\Controllers\UserController::class, 'index'])->name('index');
    Route::get('/create', [App\Http\Controllers\UserController::class, 'create'])->name('create');
    Route::post('/', [App\Http\Controllers\UserController::class, 'store'])->name('store');
    Route::get('/{id}', [App\Http\Controllers\UserController::class, 'show'])->name('show');
    Route::get('/{id}/edit', [App\Http\Controllers\UserController::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\Http\Controllers\UserController::class, 'update'])->name('update');
    Route::delete('/{id}', [App\Http\Controllers\UserController::class, 'destroy'])->name('destroy');
});