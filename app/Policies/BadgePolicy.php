
namespace App\Policies;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class BadgePolicy
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
        // Admin and event managers can view badge lists
        return $user->hasRole('admin') || $user->hasRole('event_manager');
    }

    /**
     * Determine whether the user can view the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Badge  $badge
     * @return bool
     */
    public function view(User $user, Badge $badge)
    {
        // Admin can view any badge
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event manager can view badges for their events
        if ($user->hasRole('event_manager')) {
            return $user->id === $badge->registration->event->user_id;
        }

        // Users can view their own badges
        return $user->id === $badge->registration->user_id;
    }

    /**
     * Determine whether the user can create models.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function create(User $user)
    {
        // Admin and event managers can create badges
        return $user->hasRole('admin') || $user->hasRole('event_manager');
    }

    /**
     * Determine whether the user can update the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Badge  $badge
     * @return bool
     */
    public function update(User $user, Badge $badge)
    {
        // Admin can update any badge
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event manager can update badges for their events
        return $user->hasRole('event_manager') && 
               $user->id === $badge->registration->event->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Badge  $badge
     * @return bool
     */
    public function delete(User $user, Badge $badge)
    {
        // Admin can delete any badge
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event manager can delete badges for their events
        return $user->hasRole('event_manager') && 
               $user->id === $badge->registration->event->user_id;
    }

    /**
     * Determine whether the user can print the badge.
     *
     * @param  \App\Models\User  $user
     * @param  \App\Models\Badge  $badge
     * @return bool
     */
    public function print(User $user, Badge $badge)
    {
        // Admin can print any badge
        if ($user->hasRole('admin')) {
            return true;
        }

        // Event manager can print badges for their events
        if ($user->hasRole('event_manager')) {
            return $user->id === $badge->registration->event->user_id;
        }

        // Users can print their own badges
        return $user->id === $badge->registration->user_id;
    }

    /**
     * Determine whether the user can manage badges.
     *
     * @param  \App\Models\User  $user
     * @return bool
     */
    public function manage(User $user)
    {
        // Admin and event managers can manage badges
        return $user->hasRole('admin') || $user->hasRole('event_manager');
    }
}
