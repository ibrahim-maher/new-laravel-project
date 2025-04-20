
namespace App\Policies;

use App\Models\Checkin;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class CheckinPolicy
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
        // Admin, event managers, and ushers can view check-ins
        return $user->hasRole('admin') || 
               $user->hasRole('event_manager') || 
               $user->hasRole('usher');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Checkin  $checkin
     * @return bool
     */
    public function view(User $user, Checkin $checkin)
    {
        // Admin can view any check-in
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event manager can view check-ins for their events
        if ($user->hasRole('event_manager')) {
            return $user->id === $checkin->registration->event->user_id;
        }

        // Ushers can view the check-ins they processed
        if ($user->hasRole('usher')) {
            return $user->id === $checkin->user_id;
        }

        // Users can view their own check-ins
        return $user->id === $checkin->registration->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        // Admin, event managers, and ushers can create check-ins
        return $user->hasRole('admin') || 
               $user->hasRole('event_manager') || 
               $user->hasRole('usher');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Checkin  $checkin
     * @return bool
     */
    public function update(User $user, Checkin $checkin)
    {
        // Admin can update any check-in
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event manager can update check-ins for their events
        if ($user->hasRole('event_manager')) {
            return $user->id === $checkin->registration->event->user_id;
        }

        // Ushers can update check-ins they processed
        return $user->hasRole('usher') && $user->id === $checkin->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Checkin  $checkin
     * @return bool
     */
    public function delete(User $user, Checkin $checkin)
    {
        // Only admin can delete check-ins
        return $user->hasRole('admin');
    }
}
