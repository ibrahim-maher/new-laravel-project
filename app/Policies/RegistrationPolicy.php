
namespace App\Policies;

use App\Models\Registration;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RegistrationPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        // Admin and event managers can view registration lists
        return $user->hasRole('admin') || $user->hasRole('event_manager');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Registration  $registration
     * @return bool
     */
    public function view(User $user, Registration $registration)
    {
        // Admin can view any registration
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event manager can view registrations for their events
        if ($user->hasRole('event_manager')) {
            return $user->id === $registration->event->user_id;
        }

        // Users can view their own registrations
        return $user->id === $registration->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(?User $user)
    {
        // Anyone can register for an event
        return true;
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Registration  $registration
     * @return bool
     */
    public function update(User $user, Registration $registration)
    {
        // Admin can update any registration
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event manager can update registrations for their events
        if ($user->hasRole('event_manager')) {
            return $user->id === $registration->event->user_id;
        }

        // Users can update their own registrations if the event hasn't started
        return $user->id === $registration->user_id && 
               $registration->event->start_date > now();
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Registration  $registration
     * @return bool
     */
    public function delete(User $user, Registration $registration)
    {
        // Admin can delete any registration
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event manager can delete registrations for their events
        if ($user->hasRole('event_manager')) {
            return $user->id === $registration->event->user_id;
        }

        // Users can delete their own registrations if the event hasn't started
        return $user->id === $registration->user_id && 
               $registration->event->start_date > now();
    }

    /**
     * Determine whether the user can manage the model.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function manage(User $user)
    {
        // Admin and event managers can manage registrations
        return $user->hasRole('admin') || $user->hasRole('event_manager');
    }
}
