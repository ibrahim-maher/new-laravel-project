<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class EventPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(?User $user)
    {
        // Public events can be viewed by anyone
        return true;
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User|null  $user
     * @param  \App\Models\Event  $event
     * @return bool
     */
    public function view(?User $user, Event $event)
    {
        // Public events can be viewed by anyone
        if ($event->is_public) {
            return true;
        }

        // Private events require authentication
        return $user && ($user->hasRole('admin') || $user->id === $event->user_id);
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        // Admin and event managers can create events
        return $user->hasRole('admin') || $user->hasRole('event_manager');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Event  $event
     * @return bool
     */
    public function update(User $user, Event $event)
    {
        // Admin can update any event
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event managers can only update their own events
        return $user->hasRole('event_manager') && $user->id === $event->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Event  $event
     * @return bool
     */
    public function delete(User $user, Event $event)
    {
        // Admin can delete any event
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event managers can only delete their own events
        return $user->hasRole('event_manager') && $user->id === $event->user_id;
    }

    /**
     * Determine whether the user can manage registrations for the event.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Event  $event
     * @return bool
     */
    public function manageRegistrations(User $user, Event $event)
    {
        // Admin can manage registrations for any event
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event managers can only manage registrations for their own events
        return $user->hasRole('event_manager') && $user->id === $event->user_id;
    }
}